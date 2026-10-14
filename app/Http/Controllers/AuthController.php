<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Institution;
use App\Models\PartnerProfile;

class AuthController extends Controller
{
    /**
     * Tampilkan form login
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if ($user->status === 'suspended') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda sedang ditangguhkan. Silakan hubungi admin Destinara.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            $targetUrl = match ($user->role) {
                'buyer' => route('buyer.dashboard'),
                'partner' => route('mitra.dashboard'),
                'admin' => route('admin.dashboard'),
                'super_admin', 'superadmin' => route('superadmin.dashboard'),
                default => route('buyer.dashboard'),
            };

            return redirect()->intended($targetUrl)
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ])->onlyInput('email');
    }

    /**
     * Tampilkan form registrasi akun
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.register');
    }

    /**
     * Proses pendaftaran akun baru (Buyer Institusi atau Mitra Pengelola)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'org_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:50',
            'password' => 'required|string|min:8',
            'role' => 'required|in:buyer,partner',
        ], [
            'name.required' => 'Nama PIC wajib diisi.',
            'org_name.required' => 'Nama instansi/lembaga wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'role.required' => 'Peran akun wajib dipilih.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        if ($request->role === 'buyer') {
            Institution::create([
                'user_id' => $user->id,
                'institution_name' => $request->org_name,
                'institution_type' => 'sekolah',
                'pic_name' => $request->name,
                'pic_phone' => $request->phone,
                'pic_email' => $request->email,
                'verification_status' => 'unverified',
            ]);
        } else {
            PartnerProfile::create([
                'user_id' => $user->id,
                'organization_name' => $request->org_name,
                'organization_type' => 'yayasan_adat',
                'bank_name' => 'BPD Bali',
                'verification_status' => 'pending',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $targetUrl = match ($user->role) {
            'buyer' => route('buyer.dashboard'),
            'partner' => route('mitra.dashboard'),
            default => route('buyer.dashboard'),
        };

        return redirect()->intended($targetUrl)
            ->with('success', 'Akun berhasil didaftarkan! Selamat datang di ekosistem Destinara.');
    }

    /**
     * Logout pengguna
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Helper pengalihan rute berdasarkan peran
     */
    protected function redirectBasedOnRole(User $user)
    {
        return match ($user->role) {
            'buyer' => redirect()->route('buyer.dashboard'),
            'partner' => redirect()->route('mitra.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'super_admin', 'superadmin' => redirect()->route('superadmin.dashboard'),
            default => redirect()->route('buyer.dashboard'),
        };
    }
}
