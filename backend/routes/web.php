<?php

/*use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MicrocredentialsApplicationController;

use Illuminate\Http\Request;        // NEW
use Illuminate\Support\Facades\Auth; // NEW
use Illuminate\Support\Facades\Hash; // NEW
use App\Models\User;                 // NEW

use App\Http\Controllers\Auth\MicroAuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
/*Route::prefix('api')->group(function () {
    
    
    // PUBLIC submit endpoint (guest + CSRF)
    Route::middleware(['web', 'guest', 'throttle:10,1'])->group(function () {
        Route::post('/microcredentials/apply', [MicrocredentialsApplicationController::class, 'store'])
            ->name('micro.apply.public');
    

// Login (session-based, Sanctum) — with error surface
    Route::post('/microcredentials/login', function (Request $request) {
        try {
            $data = $request->validate([
                'email'    => ['required','email'],
                'password' => ['required'],
                'remember' => ['sometimes','boolean'],
            ]);

            $user = User::where('email', $data['email'])->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                return response()->json(['ok' => false, 'message' => 'Invalid credentials'], 401);
            }

            Auth::login($user, (bool)($data['remember'] ?? false));
            $request->session()->regenerate(); // prevent fixation

            return response()->json([
                'ok'      => true,
                'message' => 'Login successful',
                'user'    => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
                'type'  => class_basename($e),
            ], 500);
        }
    })->middleware(['web', 'guest', 'throttle:30,1']); // keep 'web' for session



        // Authenticated "who am I" endpoint for the SPA
        Route::get('/me', function (\Illuminate\Http\Request $request) {
            return response()->json([
                'ok'   => true,
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ]);
        })->middleware(['web', 'auth:sanctum']);


    
    
    
    });

    // Status (requires micro.view-status)
    Route::get('/microcredentials/status', [MicrocredentialsApplicationController::class, 'status'])
        ->middleware([
            'web',
            'auth:sanctum',
            'permission:micro.view-status',
            'throttle:60,1',
    ]);

    // Logout (must be authenticated)
    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok' => true, 'message' => 'Logged out']);
    })->middleware(['web', 'auth:sanctum']);



});

require __DIR__ . '/auth.php';
*/
/*
|--------------------------------------------------------------------------
| SPA under /app
|--------------------------------------------------------------------------
| 1) Redirect /app → /app/login so login shows first
| 2) Catch-all only for /app/* and serve public/app/index.html
*/
/*Route::get('/app', fn () => file_get_contents(public_path('app/index.html')));
Route::get('/app/{any}', fn () => file_get_contents(public_path('app/index.html')))->where('any', '.*');
*/


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MicrocredentialsApplicationController;
use App\Http\Controllers\Auth\MicroAuthController;

/*
|--------------------------------------------------------------------------
| API Routes (Sanctum session lives on 'web' middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('api')->middleware(['web'])->group(function () {

    // PUBLIC submit endpoint (guest, with CSRF; throttle tighter)
    Route::middleware(['guest','throttle:10,1'])->group(function () {
        Route::post('/microcredentials/apply', [MicrocredentialsApplicationController::class, 'store'])
            ->name('micro.apply.public');
    });

    // LOGIN — JSON only, no redirects (IMPORTANT: no 'guest', no 'auth')
    Route::post('/microcredentials/login', [MicroAuthController::class, 'login'])
        ->middleware(['throttle:30,1'])
        ->name('micro.login');



    // Authenticated "who am I" for SPA
    Route::get('/me', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'ok' => true,
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ] : null,
        ]);
    })->middleware(['auth:sanctum']);

    // Status (needs permission)
    Route::get('/microcredentials/status', [MicrocredentialsApplicationController::class, 'status'])
        ->middleware(['auth:sanctum','permission:micro.view-status','throttle:60,1']);

    // Logout (JSON)
    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok' => true, 'message' => 'Logged out']);
    })->middleware(['auth:sanctum']);

    /*Route::post('/logout', function (Request $request) {
        // 1) End the auth session and rotate CSRF
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 2) Figure out cookie names/domains
        $domain = config('session.domain');                   // e.g. mportal-production.up.railway.app
        $sessionCookie = config('session.cookie', 'laravel_session'); // e.g. mportal_session

        // 3) Build cookie "forgets" (covers domain + no-domain, and "/" + "/app")
        $forgets = [
            Cookie::forget('XSRF-TOKEN', '/', $domain),
            Cookie::forget($sessionCookie, '/', $domain),
            Cookie::forget('XSRF-TOKEN', '/app', $domain),
            Cookie::forget($sessionCookie, '/app', $domain),
            Cookie::forget('XSRF-TOKEN', '/'),
            Cookie::forget($sessionCookie, '/'),
        ];

        // 4) Return JSON + attach cookie deletions
        $resp = response()->json(['ok' => true, 'message' => 'Logged out']);
        foreach ($forgets as $c) { $resp->withCookie($c); }
        return $resp;
    })->middleware(['auth:sanctum']); // routes/web.php already has 'web' by default*/


});

/*
|--------------------------------------------------------------------------
| SPA under /app  (keep these as you have them)
|--------------------------------------------------------------------------
*/
Route::get('/app', fn () => file_get_contents(public_path('app/index.html')));
Route::get('/app/{any}', fn () => file_get_contents(public_path('app/index.html')))->where('any', '.*');

require __DIR__ . '/auth.php';

