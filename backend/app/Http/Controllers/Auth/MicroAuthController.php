<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MicroAuthController extends Controller
{
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

            // 🔹 Enforce Micro app access
            if (!$user->hasApp('MICRO')) {
                Auth::logout();
                return response()->json([
                    'ok'      => false,
                    'message' => 'Access denied to Microcredentials.',
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
            ], 200);
        }

        return response()->json(['ok'=>false,'message'=>'Invalid credentials'], 401);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok' => true]);
    }
}
