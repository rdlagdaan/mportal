<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Mobile\UserDevice;
class ValidateDeviceAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
{
    $raw = $request->header('X-Device-Auth');

    if (!$raw) {
        return response()->json(['error' => 'Missing device auth token'], 401);
    }

    $hashed = hash('sha256', $raw);

    $device = UserDevice::where('auth_token_hash', $hashed)
        ->where('is_revoked', false)
        ->first();

    if (!$device) {
        return response()->json(['error' => 'Untrusted device'], 401);
    }

    // Update last used time
    $device->update(['last_used_at' => now()]);

    return $next($request);
}
}
