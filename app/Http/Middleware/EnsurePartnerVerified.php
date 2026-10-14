<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPartner()) {
            $profile = $user->partnerProfile()->first();

            if (! $profile || ! $profile->isVerified()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Akun mitra Anda belum diverifikasi oleh administrator.',
                    ], 403);
                }

                return redirect()->route('mitra.profile')->with(
                    'warning',
                    'Akun Anda belum terverifikasi. Lengkapi profil dan dokumen legalitas untuk diproses oleh administrator.'
                );
            }
        }

        return $next($request);
    }
}
