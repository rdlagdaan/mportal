<?php
namespace App\Http\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class ValidateCsrfToken extends Middleware
{
    /**
     * URIs excluded from CSRF verification.
     * Keep both with/without leading / and the namespace.
     *
     * @var array<int, string>
     */
    protected $except = [
        'app/api/lrwsis/*',
        '/app/api/lrwsis/*',
        'api/lrwsis/*',
    ];
}
