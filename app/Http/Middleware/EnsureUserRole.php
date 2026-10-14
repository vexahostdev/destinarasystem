<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();
        $userRole = $user->role;

        // Normalisasi superadmin & super_admin, serta mitra & partner
        if ($userRole === 'superadmin') {
            $userRole = 'super_admin';
        }
        if ($userRole === 'mitra') {
            $userRole = 'partner';
        }

        $allowedRoles = [];
        foreach ($roles as $r) {
            foreach (explode('|', $r) as $subRole) {
                $subRole = trim($subRole);
                if ($subRole === 'superadmin') $subRole = 'super_admin';
                if ($subRole === 'mitra') $subRole = 'partner';
                $allowedRoles[] = $subRole;
            }
        }

        if (!in_array($userRole, $allowedRoles)) {
            return match ($userRole) {
                'buyer' => redirect()->route('buyer.dashboard')->with('error', 'Anda tidak memiliki izin mengakses halaman tersebut.'),
                'partner' => redirect()->route('mitra.dashboard')->with('error', 'Anda tidak memiliki izin mengakses halaman tersebut.'),
                'admin' => redirect()->route('admin.dashboard')->with('error', 'Anda tidak memiliki izin mengakses halaman tersebut.'),
                'super_admin' => redirect()->route('superadmin.dashboard')->with('error', 'Anda tidak memiliki izin mengakses halaman tersebut.'),
                default => redirect()->route('destinasi.index')->with('error', 'Akses ditolak.'),
            };
        }

        return $next($request);
    }
}
