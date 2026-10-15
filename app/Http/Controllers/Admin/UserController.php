<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['institution', 'partnerProfile'])
            ->whereIn('account_type', [AccountType::BUYER, AccountType::PARTNER]);

        if ($request->filled('type')) {
            $query->where('account_type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $users = $query->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'total_buyers' => User::where('account_type', AccountType::BUYER)->count(),
            'total_partners' => User::where('account_type', AccountType::PARTNER)->count(),
            'suspended' => User::whereIn('account_type', [AccountType::BUYER, AccountType::PARTNER])->where('status', 'suspended')->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function toggleStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended',
            'reason' => 'required_if:status,suspended|nullable|string|max:500',
        ]);

        $user->status = $validated['status'];
        $user->suspended_reason = $validated['status'] === 'suspended' ? $validated['reason'] : null;
        $user->save();

        if ($user->status === 'suspended') {
            // Invalidate user sessions in database session store if configured
            try {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            } catch (\Throwable) {
                // Ignore if session driver is not database
            }
        }

        AuditLogger::log(
            action: 'toggle_user_status',
            target: $user,
            description: "Status pengguna {$user->name} diubah menjadi {$user->status}.".($user->suspended_reason ? " Alasan: {$user->suspended_reason}" : '')
        );

        $actionLabel = $user->status === 'active' ? 'diaktifkan kembali' : 'ditangguhkan';

        return back()->with('success', "Akun {$user->name} berhasil {$actionLabel}.");
    }

    public function sendPasswordReset(User $user)
    {
        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        AuditLogger::log(
            action: 'send_password_reset',
            target: $user,
            description: "Link reset password dikirimkan ke {$user->email}."
        );

        return back()->with('success', "Tautan reset password berhasil dikirim ke alamat email {$user->email}.");
    }

    public function verifyEmail(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('info', "Email {$user->email} sudah terverifikasi sebelumnya.");
        }

        $user->markEmailAsVerified();

        AuditLogger::log(
            action: 'manual_verify_email',
            target: $user,
            description: "Email {$user->email} diverifikasi secara manual oleh admin."
        );

        return back()->with('success', "Email {$user->email} berhasil diverifikasi secara manual.");
    }

    public function updatePartnerCommission(Request $request, User $user)
    {
        if (! $user->isPartner() || ! $user->partnerProfile) {
            return back()->with('error', 'Pengguna bukan mitra terdaftar.');
        }

        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $user->partnerProfile->commission_percent_override = $validated['commission_rate'];
        $user->partnerProfile->save();

        AuditLogger::log(
            action: 'update_partner_commission',
            target: $user->partnerProfile,
            description: "Tarif komisi khusus mitra {$user->name} diatur menjadi {$validated['commission_rate']}%."
        );

        return back()->with('success', 'Tarif komisi mitra berhasil diperbarui.');
    }
}
