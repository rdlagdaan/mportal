<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        // ✅ TEMP: allow Admin Access API while wiring SPA
        'app/api/admin/access/*',

        // (harmless to keep here in dev)
        'app/sanctum/csrf-cookie',
        'app/session-touch',

        // If you still use the temp debug routes:
        'api/admin/access/toggle-id/*',
        'api/admin/access/toggle/*',
    ];
}
