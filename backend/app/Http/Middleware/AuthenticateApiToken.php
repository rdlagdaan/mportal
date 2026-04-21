<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LwsisApp\ApiToken;
use Illuminate\Support\Facades\DB;

class AuthenticateApiToken
{

    public function handle($request, Closure $next)
{
    $authHeader = $request->header('Authorization');

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // 🔥 kunin raw token
    $plainToken = str_replace('Bearer ', '', $authHeader);

    // 🔥 IMPORTANT: hash to match DB
    $hashedToken = hash('sha256', $plainToken);

    // 🔍 hanapin sa DB
    $token = DB::table('iam.api_tokens')
        ->where('token_sha256', $hashedToken)
        ->first();

    if (!$token) {
        return response()->json(['message' => 'Invalid or expired token'], 401);
    }

    // ✅ attach user_id sa request
    $request->attributes->set('auth_user_id', $token->user_id);

    return $next($request);
}
}