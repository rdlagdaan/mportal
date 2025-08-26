<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AppCsrfCookieController extends Controller
{
    /**
     * GET /app/lrwsis/csrf-cookie
     * Must be under: ->middleware(['web','session.bucket:lrwsis'])
     * Sends exactly ONE XSRF-TOKEN cookie scoped to /app.
     */
    public function show(Request $request): Response
    {
        $domain   = config('session.domain');
        $secure   = (bool) config('session.secure', false);
        $sameSite = config('session.same_site', 'lax');

        // Build a 204 No Content response
        $resp = response()->noContent();

        // Ensure ONLY /app XSRF-TOKEN exists
        $resp->withCookie(cookie(
            name:     'XSRF-TOKEN',
            value:    $request->session()->token(), // equals csrf_token()
            minutes:  120,
            path:     '/app',
            domain:   $domain,
            secure:   $secure,
            httpOnly: false,
            sameSite: $sameSite
        ));

        // Explicitly clear any stray /lrwsis variant if present
        $resp->withCookie(Cookie::forget('XSRF-TOKEN', '/lrwsis', $domain));

        return $resp;
    }
}
