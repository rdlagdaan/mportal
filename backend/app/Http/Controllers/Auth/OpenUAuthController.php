<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpenUAuthController extends Controller
{
    /**
     * POST /open/api/login
     * Body: { email, password, remember? }
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
            'remember' => ['sometimes','boolean'],
        ]);

        if (Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            (bool)($data['remember'] ?? false)
        )) {
            $request->session()->regenerate();
            $user = $request->user();

            // Enforce OPENU app access
            if (!$user->hasApp('OPENU')) {
                Auth::logout();
                return response()->json([
                    'ok'      => false,
                    'message' => 'Access denied to TUA Open University.',
                    'code'    => 'APP_ACCESS_DENIED',
                ], 403);
            }

            return response()->json([
                'ok'   => true,
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return response()->json(['ok'=>false,'message'=>'Invalid credentials'], 401);
    }

    /**
     * GET /open/api/me
     * Requires: auth:sanctum + app.access:OPENU
     */
    public function me(Request $request)
    {
        return response()->json(['ok'=>true,'user'=>$request->user()?->only(['id','name','email'])]);
    }

    /**
     * POST /open/api/logout
     * Requires: auth:sanctum + app.access:OPENU
     */
    public function logout(Request $request)
    {
        try { Auth::guard('web')->logout(); } catch (\Throwable $e) {}
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok'=>true]);
    }
}
