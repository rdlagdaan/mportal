<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MicrocredentialsApplicationController;



Route::get('/', fn () => 'OK');

Route::get('/health', fn () => response()->json(['ok' => true]));
Route::prefix('api')->group(function () {
    Route::get('/health', fn () => response()->json(['ok' => true]));
});
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
| SPA Fallback Route
|--------------------------------------------------------------------------
| Keep this LAST to serve the React app for any unmatched route.
*/
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');
