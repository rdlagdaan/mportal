<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SessionBucket
{
    /**
     * Use as: ->middleware('session.bucket:lrwsis')
     * Buckets:
     *  - lrwsis => cookie lrwsis_session, path /app
     *  - micro  => cookie micro_session,  path /app
     *  - open   => cookie open_session,   path /app
     */
    public function handle(Request $request, Closure $next, string $bucket = null)
    {
        if ($bucket) {
            $cookieName = match ($bucket) {
                'lrwsis' => 'lrwsis_session',
                'micro'  => 'micro_session',
                'open'   => 'open_session',
                default  => config('session.cookie', 'laravel_session'),
            };

            // IMPORTANT: mutate config BEFORE StartSession runs
            config([
                'session.cookie'     => $cookieName,
                'session.path'       => '/app',                         // scope to /app
                'session.domain'     => config('session.domain'),       // leave as-is
                'session.same_site'  => config('session.same_site', 'lax'),
            ]);
        }

        return $next($request);
    }
}
