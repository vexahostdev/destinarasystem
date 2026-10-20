<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Enums\VerificationStatus;
use App\Events\UserRegistered;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Institution;
use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan form login (GET /masuk)
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnAccountType(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login (POST /masuk)
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Check active / suspended status
        if ($user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $reason = $user->suspended_reason ? ': '.$user->suspended_reason : '.';

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda sedang ditangguhkan oleh administrator'.$reason,
            ])->onlyInput('email');
        }

        // Record login audit and metadata
        $user->updateQuietly([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        AuditLogger::log(
            action: 'login',
            target: $user,
            description: 'User login berhasil ('.$user->account_type->value.')'
        );

        $request->session()->regenerate();

        return redirect()->intended($this->getTargetDashboardUrl($user))
            ->with('success', 'Selamat datang kembali, '.$user->name.'!');
    }

    /**
     * Tampilkan form registrasi akun (GET /daftar)
     */
    public function showRegisterForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnAccountType(Auth::user());
        }

        return view('auth.register');
    }

    /**
     * Proses pendaftaran akun baru (POST /daftar)
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        // Rate limit register: 3 attempts per minute per IP
        $throttleKey = 'register|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan pendaftaran. Silakan coba kembali dalam {$seconds} detik.",
            ])->withInput();
        }
        RateLimiter::hit($throttleKey, 60);

        $data = $request->validated();
        $accountType = $data['account_type'] ?? $request->input('role', 'buyer');

        // Normalize phone: 08xx -> 628xx
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        $user = DB::transaction(function () use ($data, $accountType, $phone) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => Hash::make($data['password']),
                'account_type' => $accountType,
                'phone' => $phone,
                'status' => 'active',
            ]);

            if ($accountType === 'buyer') {
                Institution::create([
                    'user_id' => $user->id,
                    'institution_name' => $data['org_name'],
                    'pic_name' => $data['name'],
                    'pic_phone' => $phone,
                    'pic_email' => $data['email'],
                    'verification_status' => VerificationStatus::UNVERIFIED,
                ]);
            } else {
                PartnerProfile::create([
                    'user_id' => $user->id,
                    'organization_name' => $data['org_name'],
                    'verification_status' => VerificationStatus::UNVERIFIED,
                ]);
            }

            return $user;
        });

        AuditLogger::log(
            action: 'register',
            target: $user,
            description: "Registrasi akun baru ({$user->account_type->value})",
            userId: $user->id
        );

        event(new Registered($user));
        event(new UserRegistered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', 'Akun berhasil didaftarkan! Silakan cek email Anda untuk verifikasi akun.');
    }

    /**
     * Logout pengguna (POST /keluar)
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditLogger::log(
                action: 'logout',
                target: $user,
                description: 'User logout dari sesi'
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Tampilkan form lupa password (GET /lupa-password)
     */
    public function showForgotPasswordForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnAccountType(Auth::user());
        }

        return view('auth.forgot-password');
    }

    /**
     * Kirim email reset password (POST /lupa-password)
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse
    {
        $throttleKey = 'forgot-password|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Terlalu banyak permintaan reset password. Tunggu {$seconds} detik.",
            ]);
        }
        RateLimiter::hit($throttleKey, 60);

        Password::sendResetLink($request->only('email'));

        // Pesan selalu sama agar tidak membocorkan apakah email terdaftar (Audit v11 K-5)
        return back()->with('status', 'Jika email tersebut terdaftar, tautan reset password telah dikirim. Silakan periksa kotak masuk Anda.');
    }

    /**
     * Tampilkan form reset password (GET /reset-password/{token})
     */
    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Simpan kata sandi baru (POST /reset-password)
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditLogger::log(
                    action: 'password_reset',
                    target: $user,
                    description: 'Kata sandi akun berhasil diatur ulang'
                );
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', trans($status));
        }

        return back()->withErrors(['email' => trans($status)]);
    }

    /**
     * Tampilkan pemberitahuan verifikasi email (GET /email/verifikasi)
     */
    public function showVerificationNotice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended($this->getTargetDashboardUrl($request->user()))
            : view('auth.verify-email');
    }

    /**
     * Verifikasi email pengguna (GET /email/verifikasi/{id}/{hash})
     */
    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        AuditLogger::log(
            action: 'email_verified',
            target: $request->user(),
            description: 'Email akun berhasil diverifikasi'
        );

        return redirect()->intended($this->getTargetDashboardUrl($request->user()))
            ->with('success', 'Email Anda telah berhasil diverifikasi! Selamat berkegiatan bersama Destinara.');
    }

    /**
     * Kirim ulang link verifikasi email (POST /email/verifikasi/kirim-ulang)
     */
    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->getTargetDashboardUrl($request->user()));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Dapatkan URL dashboard berdasarkan tipe akun
     */
    public function getTargetDashboardUrl(User $user): string
    {
        if ($user->isBuyer()) {
            return route('buyer.dashboard');
        }
        if ($user->isPartner()) {
            return route('partner.dashboard');
        }
        if ($user->isInternal()) {
            return route('admin.dashboard');
        }

        return route('home');
    }

    /**
     * Redirect helper berdasarkan tipe akun
     */
    public function redirectBasedOnAccountType(User $user): RedirectResponse
    {
        return redirect()->to($this->getTargetDashboardUrl($user));
    }
}
