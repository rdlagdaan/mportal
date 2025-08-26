<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        App\Providers\BroadcastChannelsServiceProvider::class, // ← add this line
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // 1) Aliases — YES, include SessionBucket here
        $middleware->alias([
            'session.bucket'     => \App\Http\Middleware\SessionBucket::class,
            'app.access'         => \App\Http\Middleware\EnsureUserHasAppAccess::class,
            'verified'           => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'force.bucket.cookie' => \App\Http\Middleware\ForceBucketedSessionCookie::class,
        ]);

        // 2) Web stack — DO NOT include SessionBucket here
        //    Use only the core CSRF middleware (ValidateCsrfToken), not your custom VerifyCsrfToken.
        $middleware->web([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            EnsureFrontendRequestsAreStateful::class,
            HandleCors::class,
        ]);

        // 3) Global priority — YES, include SessionBucket here before StartSession
        $middleware->priority([
            \App\Http\Middleware\SessionBucket::class, // must run before StartSession when present on a route
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\ForceBucketedSessionCookie::class,  // ← add here
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            EnsureFrontendRequestsAreStateful::class,
            HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
