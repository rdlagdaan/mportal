<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/health',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Project aliases (registration only)
        $middleware->alias([
            // buckets & helpers
            'session.bucket'           => \App\Http\Middleware\SessionBucket::class,
            'force.bucket.cookie'      => \App\Http\Middleware\ForceBucketCookie::class,
            'force.bucketed.session'   => \App\Http\Middleware\ForceBucketedSessionCookie::class,
            'force.web.guard'          => \App\Http\Middleware\ForceWebGuard::class,
            'ensure.user.app'          => \App\Http\Middleware\EnsureUserHasAppAccess::class,
            'csrf.except.broadcasting' => \App\Http\Middleware\CsrfExceptBroadcasting::class,

            // Spatie Permission
            'role'                     => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'               => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'       => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // DB schema selector
            'db.assets'                => \App\Http\Middleware\UseAssetsSchema::class,

            //mobile v2
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,

        ]);

        // Apply schema selection to the web stack
        $middleware->web(append: ['db.assets']);
        $middleware->group('api', []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
