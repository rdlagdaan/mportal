<?php
// app/Http/Controllers/Auth/MicroAuthController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MicroAuthController extends Controller
{
    public function login(Request $request)
    {
        $creds = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
            'remember' => ['sometimes','boolean'],
        ]);

        if (Auth::attempt(
            ['email'=>$creds['email'],'password'=>$creds['password']],
            $creds['remember'] ?? false
        )) {
            $request->session()->regenerate();
            return response()->json(['ok'=>true], 200);
        }

        return response()->json(['ok'=>false,'message'=>'Invalid credentials'], 401);
    }
}
