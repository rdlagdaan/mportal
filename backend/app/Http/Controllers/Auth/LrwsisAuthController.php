<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// If you have spatie activitylog installed (we'll no-op if not configured)
use function activity;

class LrwsisAuthController extends Controller
{
    /**
     * POST /app/api/lrwsis/login
     * Body: { email, password, remember? }
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
            'remember' => ['sometimes','boolean'],
        ]);

        $remember = $request->boolean('remember');

        $ok = Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $remember
        );

        if (! $ok) {
            // activity log (optional)
            try { activity('auth')->withProperties(['email' => $data['email']])->log('lrwsis_login_failed'); } catch (\Throwable $e) {}
            return response()->json(['ok' => false, 'message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();
        $user = $request->user();

        // ---- App access guard -----------------------------------------------
        // Prefer your hasApp('LRWSIS'); otherwise fall back to a Spatie role.
        $hasAccess = method_exists($user, 'hasApp')
            ? $user->hasApp('LRWSIS')
            : (method_exists($user, 'hasRole') ? $user->hasRole('lrwsis_student') : false);

        if (! $hasAccess) {
            Auth::logout();
            return response()->json([
                'ok'      => false,
                'message' => 'Access denied to LRWSIS.',
                'code'    => 'APP_ACCESS_DENIED',
            ], 403);
        }
        // ---------------------------------------------------------------------

        // activity log (optional)
        try { activity('auth')->performedOn($user)->log('lrwsis_login_success'); } catch (\Throwable $e) {}

        return response()->json([
            'ok'   => true,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * GET /app/api/lrwsis/me
     * Requires: auth:sanctum (+ optional app access middleware)
     */
    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'ok'   => (bool) $u,
            'user' => $u?->only(['id','name','email']),
        ], $u ? 200 : 401);
    }

    /**
     * POST /app/api/lrwsis/logout
     * Requires: auth:sanctum (+ optional app access middleware)
     */
    public function logout(Request $request)
    {
        try { activity('auth')->performedOn($request->user())->log('lrwsis_logout'); } catch (\Throwable $e) {}

        try { Auth::guard('web')->logout(); } catch (\Throwable $e) {}

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
