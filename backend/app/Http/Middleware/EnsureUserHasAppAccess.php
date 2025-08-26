<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware(['auth:sanctum','app.access:LRWSIS'])
 * Logic:
 * 1) If User::hasApp($code) exists, use it.
 * 2) Else, check Spatie roles/permissions via config('app_access.map').
 *    - super-admin bypasses all.
 */
class EnsureUserHasAppAccess
{
    public function handle(Request $request, Closure $next, string $appCode): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ok'=>false,'message'=>'Unauthenticated'], 401);
        }

        // super-admin bypass
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return $next($request);
        }

        // 1) Prefer app-specific method if present
        if (method_exists($user, 'hasApp')) {
            if ($user->hasApp($appCode)) {
                return $next($request);
            }
        }

        // 2) Fallback to Spatie roles/permissions mapping
        $map = config('app_access.map', []);
        $required = $map[$appCode] ?? [];

        $hasRole = false;
        $hasPerm = false;

        if (method_exists($user, 'hasAnyRole') && !empty($required['roles'])) {
            $hasRole = $user->hasAnyRole($required['roles']);
        }
        if (method_exists($user, 'hasAnyPermission') && !empty($required['permissions'])) {
            $hasPerm = $user->hasAnyPermission($required['permissions']);
        }

        if ($hasRole || $hasPerm) {
            return $next($request);
        }

        return response()->json([
            'ok'      => false,
            'message' => "Access denied to {$appCode}.",
            'code'    => 'APP_ACCESS_DENIED',
        ], 403);
    }
}
