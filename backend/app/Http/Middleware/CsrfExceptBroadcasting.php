<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class CsrfExceptBroadcasting extends ValidateCsrfToken
{
    /**
     * URIs that should be excluded from CSRF verification.
     *
     * Note: In Laravel 12 the core class is ValidateCsrfToken,
     * not VerifyCsrfToken.
     */
    protected $except = [
        'broadcasting/auth',
        'app/broadcasting/auth',   // keep in case a prefixed route slips through
    ];
}
