<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MicrocredentialsApplicationController;

use Illuminate\Http\Request;        // NEW
use Illuminate\Support\Facades\Auth; // NEW
use Illuminate\Support\Facades\Hash; // NEW
use App\Models\User;                 // NEW


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('api')->group(function () {
    
    
    // PUBLIC submit endpoint (guest + CSRF)
    Route::middleware(['web', 'guest', 'throttle:10,1'])->group(function () {
        Route::post('/microcredentials/apply', [MicrocredentialsApplicationController::class, 'store'])
            ->name('micro.apply.public');
    

        // NEW: Microcredentials login (session-based via Sanctum)
        Route::post('/microcredentials/login', function (Request $request) {
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

            return response()->json([
                'ok'      => true,
                'message' => 'Login successful',
                'user'    => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ]);
        });



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

/*
|--------------------------------------------------------------------------
| SPA under /app
|--------------------------------------------------------------------------
| 1) Redirect /app → /app/login so login shows first
| 2) Catch-all only for /app/* and serve public/app/index.html
*/
Route::get('/app', fn () => file_get_contents(public_path('app/index.html')));
Route::get('/app/{any}', fn () => file_get_contents(public_path('app/index.html')))->where('any', '.*');

