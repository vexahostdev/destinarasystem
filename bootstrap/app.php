<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAccountType;
use App\Http\Middleware\EnsurePartnerVerified;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Append to web group
        $middleware->web(append: [
            SecurityHeaders::class,
            CheckMaintenanceMode::class,
        ]);

        // Exclude webhooks from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/*',
        ]);

        // Middleware Aliases
        $middleware->alias([
            'account' => EnsureAccountType::class,
            'active' => EnsureAccountActive::class,
            'partner.verified' => EnsurePartnerVerified::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
