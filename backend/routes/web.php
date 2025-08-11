<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MicrocredentialsApplicationController;

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
    });

    // Status (requires micro.view-status)
    Route::get('/microcredentials/status', [MicrocredentialsApplicationController::class, 'status'])
        ->middleware([
            'web',
            'auth:sanctum',
            'permission:micro.view-status',
            'throttle:60,1',
        ]);
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

