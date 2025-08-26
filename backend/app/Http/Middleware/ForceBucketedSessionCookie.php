<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class ForceBucketedSessionCookie
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $expectedName = config('session.cookie', 'laravel_session'); // e.g., lrwsis_session
        $expectedPath = config('session.path', '/');                 // e.g., /app
        $domain       = config('session.domain');
        $secure       = (bool) config('session.secure', false);
        $sameSite     = config('session.same_site', 'lax');

        $sessionId = $request->session()->getId();

        if ($sessionId) {
            // 1) Ensure the bucketed cookie exists (name/path you configured)
            $response->headers->setCookie(new Cookie(
                $expectedName,
                $sessionId,
                now()->addMinutes(config('session.lifetime', 120)),
                $expectedPath,
                $domain,
                $secure,
                true,   // HttpOnly
                false,
                $sameSite
            ));

            // 2) Remove the default cookie if it was set earlier in the stack
            //    a) Clear with a past expiration
            $response->headers->setCookie(new Cookie(
                'laravel_session',
                '',
                now()->subYear(),
                '/',
                $domain,
                $secure,
                true,
                false,
                $sameSite
            ));
            //    b) Also use clearCookie for good measure (some clients respect one or the other)
            $response->headers->clearCookie('laravel_session', '/', $domain);
        }

        return $response;
    }
}
