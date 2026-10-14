<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountType
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $accountType): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $currentType = is_object($user->account_type) ? $user->account_type->value : (string) $user->account_type;

        if ($currentType !== $accountType) {
            // If user is accessing wrong portal, redirect to their home portal
            if ($user->isBuyer()) {
                return redirect()->route('buyer.dashboard')->with('error', 'Akses ditolak untuk tipe akun Anda.');
            }
            if ($user->isPartner()) {
                return redirect()->route('partner.dashboard')->with('error', 'Akses ditolak untuk tipe akun Anda.');
            }
            if ($user->isInternal()) {
                return redirect()->route('admin.dashboard')->with('error', 'Akses ditolak untuk tipe akun Anda.');
            }

            abort(403, 'Akses tidak diizinkan untuk tipe akun ini.');
        }

        return $next($request);
    }
}
