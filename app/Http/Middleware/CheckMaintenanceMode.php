<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = (bool) setting('maintenance_mode', false);

        if ($isMaintenance) {
            // Whitelisted paths
            $path = $request->path();
            if (
                str_starts_with($path, 'admin') ||
                str_starts_with($path, 'webhooks') ||
                str_starts_with($path, 'masuk') ||
                str_starts_with($path, 'keluar') ||
                str_starts_with($path, 'login') ||
                str_starts_with($path, 'logout')
            ) {
                return $next($request);
            }

            // Internal users can access during maintenance
            if ($request->user() && $request->user()->isInternal()) {
                return $next($request);
            }

            $message = setting('maintenance_message', 'Situs Destinara sedang dalam peningkatan sistem berkala.');

            return response()->view('errors.maintenance', [
                'message' => $message,
            ], 503);
        }

        return $next($request);
    }
}
