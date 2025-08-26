<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

use App\Http\Controllers\LrwsisModuleController;
use App\Http\Controllers\ModuleAccessController;
use App\Http\Controllers\ApplicationSettingsController;

use App\Http\Controllers\Admin\UserAccessController;

use App\Http\Controllers\Auth\MicroAuthController;
use App\Http\Controllers\Auth\LrwsisAuthController;
use App\Http\Controllers\Auth\OpenUAuthController;
use App\Http\Controllers\Auth\AppCsrfCookieController;

use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

use App\Http\Controllers\Assets\AssetGroupController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\DepreciationController;
use App\Http\Controllers\Assets\DepartmentGlController;


use App\Http\Controllers\Assets\AssetClassController;
use App\Http\Controllers\Assets\AssetCategoryController;
use App\Http\Controllers\Assets\AssetTypeController;


use App\Http\Controllers\Inventory\GatepassController;
use App\Http\Controllers\Inventory\IssuanceReturnController;
use App\Http\Controllers\Inventory\ProblemSolutionController;
use App\Http\Controllers\Inventory\DisposalRetirementController;

use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;


use App\Http\Controllers\HRSI\EmployeeController;
use App\Http\Controllers\HRSI\EmployeeEducationController;
use App\Http\Controllers\HRSI\EmployeeAppointmentController;

use App\Http\Controllers\Api\NotificationsController;

use App\Http\Controllers\HRSI\LeaveManagement\LeaveRequestController;
use App\Http\Controllers\HRSI\LeaveManagement\LeaveApprovalController;
use App\Http\Controllers\HRSI\LeaveManagement\LeavePostingController;
use App\Http\Controllers\HRSI\LeaveManagement\LeaveReportController;

use App\Http\Controllers\Assets\QrController;   // ← add this line
use Illuminate\Support\Facades\Broadcast;

use App\Http\Controllers\Assets\LookupController;
use App\Http\Controllers\AuditLogController;

/*
|--------------------------------------------------------------------------
| Health / Ping
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json(['ok' => true]))->name('health');
Route::get('api/ping', fn () => response()->json(['pong' => true]))->name('ping');

/*
|--------------------------------------------------------------------------
| MICRO (Microcredentials) API under /app/api/microcredentials
| Uses session.bucket:micro and also serves the CSRF cookie at /app/sanctum/csrf-cookie
|--------------------------------------------------------------------------
*/
Route::prefix('app')
    ->middleware(['web', 'session.bucket:micro'])
    ->group(function () {

        Route::get('/ping', fn () => response()->json(['pong' => true]))->name('app.ping');

        // CSRF cookie scoped to /app (OK for SPA usage)
        Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
            ->name('app.sanctum.csrf');

        // Admin access API: /app/api/admin/access/...
        Route::prefix('api/admin/access')->group(function () {
            Route::get('search', [UserAccessController::class, 'search'])
                ->name('admin.access.search.app');

            // CSRF enabled + numeric constraint
            Route::patch('{id}', [UserAccessController::class, 'updateById'])
                ->whereNumber('id')
                ->name('admin.access.update.app');
        });
    });

Route::prefix('app/api/microcredentials')
    ->middleware(['web', 'session.bucket:micro'])
    ->group(function () {
        Route::post('/login', [MicroAuthController::class, 'login'])
            ->middleware('throttle:30,1')
            ->name('micro.login');

        Route::get('/me', fn (Request $r) => response()->json([
            'ok'   => true,
            'user' => $r->user()?->only(['id','name','email']),
        ]))
            ->middleware(['auth:sanctum','app.access:MICRO'])
            ->name('micro.me');

        Route::post('/logout', function (Request $request) {
            try { Auth::guard('web')->logout(); } catch (\Throwable $e) {}
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $domain = config('session.domain');
            $resp = response()->json(['ok'=>true]);

            foreach ([
                ['XSRF-TOKEN','/app'], ['XSRF-TOKEN','/'],
                ['micro_session','/app'], ['laravel_session','/'],
            ] as [$name,$path]) {
                $resp->withCookie(Cookie::forget($name, $path, $domain));
                $resp->withCookie(Cookie::forget($name, $path));
            }
            return $resp;
        })
            ->middleware(['auth:sanctum','app.access:MICRO'])
            ->name('micro.logout');
    });

/*
|--------------------------------------------------------------------------
| LRWSIS API under /app (lrwsis bucket)
| Includes CSRF cookie endpoint at /app/lrwsis/csrf-cookie
|--------------------------------------------------------------------------
*/

// Explicit POST broadcast auth route (highest priority)
//Route::post('/app/broadcasting/auth', [BroadcastController::class, 'authenticate'])
//    ->middleware(['web','session.bucket:lrwsis','force.bucket.cookie','auth:sanctum']);

Broadcast::routes([
    'prefix' => 'app/api', // now matches Echo’s /app/api/broadcasting/auth
    'middleware' => ['web','session.bucket:lrwsis','force.bucket.cookie','auth:sanctum'],
]);

require base_path('routes/channels.php');

Route::prefix('app')
  ->middleware(['web', 'session.bucket:lrwsis', 'force.bucket.cookie']) // ← this is where SessionBucket is applied
  ->group(function () {

    Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
        //Route::post('/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate']);
    
        Route::get('/notifications', [NotificationsController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationsController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead']);
    });

    //Route::get('/api/assets/lookups/classes', [LookupController::class, 'classes'])
    //    ->middleware(['web','auth:sanctum','permission:fa.asset.view']);

    // TEMP: remove permission so we can test the endpoint
    Route::get('/api/assets/lookups/classes', [\App\Http\Controllers\Assets\LookupController::class, 'classes'])
        ->middleware(['web','auth:sanctum']);


    Route::get('/api/assets/lookups/categories', [LookupController::class, 'categories'])
        ->middleware(['web','auth:sanctum']);


    Route::get('/api/lookups/depreciation-types', [LookupController::class, 'depreciationTypes'])
        ->middleware(['web','auth:sanctum']);


    // Types by category for the AssetDetailModal
    Route::get('/api/assets/types-by-cat', [\App\Http\Controllers\Assets\LookupController::class, 'typesByCat'])
        ->middleware(['web','auth:sanctum']); // add permission later if needed




    Route::get('/lrwsis/_debug-session-config', function () {
        return response()->json([
            'cookie' => config('session.cookie'),
            'path'   => config('session.path'),
            'domain' => config('session.domain'),
            'same_site' => config('session.same_site'),
        ]);
    });


    Route::get('/lrwsis/csrf-cookie', [\App\Http\Controllers\Auth\AppCsrfCookieController::class, 'show']);
    Route::prefix('api/lrwsis')->group(function () {
        Route::post('/login',  [\App\Http\Controllers\Auth\LrwsisAuthController::class, 'login'])->middleware('throttle:30,1');
        Route::get('/me',      [\App\Http\Controllers\Auth\LrwsisAuthController::class, 'me'])->middleware(['auth:sanctum','app.access:LRWSIS']);
        Route::post('/logout', [\App\Http\Controllers\Auth\LrwsisAuthController::class, 'logout'])->middleware(['auth:sanctum','app.access:LRWSIS']);
    });

    Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
        // 3-level menu for the logged-in user
        Route::get('user/modules', [ModuleAccessController::class, 'userModules'])
            ->name('user.modules');
        Route::get('lrwsis/modules', [ModuleAccessController::class, 'userModules']);

    });


    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead']);



// --- FixedAssets: AssetGroup list + CRUD ---
    Route::get   ('/assets/groups',      [AssetGroupController::class, 'index'])->middleware('permission:asset_group.view');
    Route::post  ('/assets/groups',      [AssetGroupController::class, 'store'])->middleware('permission:asset_group.create');
    Route::patch ('/assets/groups/{id}', [AssetGroupController::class, 'update'])->middleware('permission:asset_group.update');
    Route::delete('/assets/groups/{id}', [AssetGroupController::class, 'destroy'])->middleware('permission:asset_group.delete');


    // Classes
    Route::get   ('/api/assets/classes',      [AssetClassController::class, 'index'])
        ->middleware(['auth:sanctum','permission:fa.class.view,web']);
    Route::post  ('/api/assets/classes',      [AssetClassController::class, 'store'])
        ->middleware(['auth:sanctum','permission:fa.class.create,web']);
    Route::patch ('/api/assets/classes/{id}', [AssetClassController::class, 'update'])
        ->middleware(['auth:sanctum','permission:fa.class.update,web']);
    Route::delete('/api/assets/classes/{id}', [AssetClassController::class, 'destroy'])
        ->middleware(['auth:sanctum','permission:fa.class.delete,web']);


    // Categories
    Route::get   ('/api/assets/categories',       [AssetCategoryController::class, 'index'])
        ->middleware(['auth:sanctum','permission:fa.category.view,web']);
    Route::post  ('/api/assets/categories',       [AssetCategoryController::class, 'store'])
        ->middleware(['auth:sanctum','permission:fa.category.create,web']);
    Route::patch ('/api/assets/categories/{id}',  [AssetCategoryController::class, 'update'])
        ->middleware(['auth:sanctum','permission:fa.category.update,web']);
    Route::delete('/api/assets/categories/{id}',  [AssetCategoryController::class, 'destroy'])
        ->middleware(['auth:sanctum','permission:fa.category.delete,web']);

    // Types
    Route::get   ('/api/assets/types',            [AssetTypeController::class, 'index'])
        ->middleware(['auth:sanctum','permission:fa.type.view,web']);
    Route::post  ('/api/assets/types',            [AssetTypeController::class, 'store'])
        ->middleware(['auth:sanctum','permission:fa.type.create,web']);
    Route::patch ('/api/assets/types/{id}',       [AssetTypeController::class, 'update'])
        ->middleware(['auth:sanctum','permission:fa.type.update,web']);
    Route::delete('/api/assets/types/{id}',       [AssetTypeController::class, 'destroy'])
        ->middleware(['auth:sanctum','permission:fa.type.delete,web']);
  
    Route::get('/api/settings/{code}', [ApplicationSettingsController::class, 'getSetting']);
    Route::get('assets', [\App\Http\Controllers\AssetController::class, 'index']);  

    // Next code (preview/commit at asset creation time)
    Route::get('/assets/types/{id}/next-code', [AssetTypeController::class, 'nextCode'])->middleware('permission:fa.type.view');




    // --- FixedAssets: AssetDetail header + tabs ---
    Route::get   ('assets',                [AssetController::class, 'index']);   // search & paginate
    Route::post  ('assets',                [AssetController::class, 'store']);   // Save and New
    Route::get   ('assets/{id}',           [AssetController::class, 'show']);    // load tabs
    Route::patch ('assets/{id}',           [AssetController::class, 'update']);
    Route::delete('assets/{id}',           [AssetController::class, 'destroy']);

    // tabs
    Route::post  ('assets/{id}/service',   [AssetController::class, 'addService']);
    Route::get   ('assets/{id}/service',   [AssetController::class, 'listService']);
    Route::post  ('assets/{id}/notes',     [AssetController::class, 'addNote']);
    Route::get   ('assets/{id}/notes',     [AssetController::class, 'listNotes']);
    Route::get   ('assets/{id}/history',   [AssetController::class, 'listHistory']);
    Route::post  ('assets/{id}/picture',   [AssetController::class, 'addPicture']);
    Route::get   ('assets/{id}/picture',   [AssetController::class, 'listPictures']);

    // depreciation & department GL
    Route::get ('assets/depreciation', [DepreciationController::class, 'index']);
    Route::post('assets/depreciation/process', [DepreciationController::class, 'process']);    
    Route::post  ('assets/{id}/depreciate', [DepreciationController::class, 'runForAsset']);
    Route::get   ('assets/{id}/depreciation', [DepreciationController::class, 'listForAsset']);


    Route::get   ('assets/department-gl',  [DepartmentGlController::class, 'index']);
    Route::post  ('assets/department-gl',  [DepartmentGlController::class, 'store']);
    Route::patch ('assets/department-gl/{id}', [DepartmentGlController::class, 'update']);
    Route::delete('assets/department-gl/{id}', [DepartmentGlController::class, 'destroy']);


    Route::apiResource('inv/issuance-return',    IssuanceReturnController::class)->only(['index','store','update','destroy','show']);
    Route::apiResource('inv/problem-solution',   ProblemSolutionController::class)->only(['index','store','update','destroy','show']);
    Route::apiResource('inv/disposal-retirement',DisposalRetirementController::class)->only(['index','store','update','destroy','show']);


 
    Route::post('/assets/{id}/depreciation/preview', [DepreciationController::class, 'preview'])
        ->middleware(['permission:fa.asset.view','web','auth:sanctum']);   


    Route::post('/assets/{id}/depreciation/export', [DepreciationController::class, 'export'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.view']);

    Route::post('/assets/{id}/depreciation/save', [DepreciationController::class, 'save'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.update']);

    Route::get('/assets/{id}/depreciation/schedules', [DepreciationController::class, 'list'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.view']);

    Route::get('/depreciation/schedules/{sid}', [DepreciationController::class, 'showSchedule'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.view']);

    Route::delete('/depreciation/schedules/{sid}', [DepreciationController::class, 'deleteSchedule'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.update']);



    Route::get('/lookups/asset-types', [LookupController::class,'assetTypes'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.view']);

    Route::get('/lookups/vendors', [LookupController::class,'vendors'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.view']);

    Route::get('/audit-logs', [AuditLogController::class,'index'])
        ->middleware(['web','auth:sanctum','permission:fa.asset.view']);


    Route::prefix('inv')->group(function () {

        // Grid/search
        Route::get('issuance-return', [IssuanceReturnController::class, 'index']);
        Route::post('issuance-return', [IssuanceReturnController::class, 'store']);
        Route::patch('issuance-return/{id}', [IssuanceReturnController::class, 'update']);
        Route::delete('issuance-return/{id}', [IssuanceReturnController::class, 'destroy']);
        Route::get('issuance-return/{id}', [IssuanceReturnController::class, 'show']);

        // Actions per asset
        Route::post('issuance/{asset}', [IssuanceReturnController::class, 'assign']);        // batch/individual
        Route::post('return/{asset}',   [IssuanceReturnController::class, 'return']);        // batch/individual
        Route::post('serials/{asset}',  [IssuanceReturnController::class, 'assignSerials']); // starting serial

        // (Optional) view journal lines
        Route::get('issuance-return/journal', [IssuanceReturnController::class, 'journal']);

        // Problem Solution (CRUD + search)
        Route::get   ('problem-solution',       [ProblemSolutionController::class, 'index']);
        Route::post  ('problem-solution',       [ProblemSolutionController::class, 'store']);
        Route::patch ('problem-solution/{id}',  [ProblemSolutionController::class, 'update']);
        Route::delete('problem-solution/{id}',  [ProblemSolutionController::class, 'destroy']);

        // Gatepass (header + items)
        Route::get   ('gatepass',               [GatepassController::class, 'index']);
        Route::post  ('gatepass',               [GatepassController::class, 'store']);
        Route::get   ('gatepass/{id}',          [GatepassController::class, 'show']);
        Route::patch ('gatepass/{id}',          [GatepassController::class, 'update']);
        Route::delete('gatepass/{id}',          [GatepassController::class, 'destroy']);
        // Line items
        Route::post  ('gatepass/{id}/items',    [GatepassController::class, 'addItem']);
        Route::delete('gatepass/items/{itemId}',[GatepassController::class, 'removeItem']);
        // Status transitions
        Route::post  ('gatepass/{id}/submit',   [GatepassController::class, 'submit']);   // OPEN -> SUBMITTED
        Route::post  ('gatepass/{id}/close',    [GatepassController::class, 'close']);    // SUBMITTED -> CLOSED

        // Sales/Disposal/Retirement
        Route::get   ('disposal-retirement',               [DisposalRetirementController::class, 'index']);
        Route::post  ('disposal-retirement',               [DisposalRetirementController::class, 'store']);
        Route::patch ('disposal-retirement/{id}',          [DisposalRetirementController::class, 'update']);
        Route::delete('disposal-retirement/{id}',          [DisposalRetirementController::class, 'destroy']);
        Route::post  ('disposal-retirement/{id}/finalize', [DisposalRetirementController::class, 'finalize']); // sets asset status & freezes depreciation
    });

    // QR codes (asset-level and per custody unit)
    Route::get('assets/{asset}/qr.svg',        [QrController::class, 'assetSvg']);        // public within /app (auth still applied)
    Route::get('assets/{asset}/custody/{seq}/qr.svg', [QrController::class, 'custodySvg']);



    Route::prefix('api/hrsi')->group(function () {

        Route::get   ('/lookups', [EmployeeController::class, 'lookups']);

        Route::get   ('/employees',                [EmployeeController::class, 'index']);
        Route::post  ('/employees',                [EmployeeController::class, 'store']);
        Route::get   ('/employees/{id}',           [EmployeeController::class, 'show']);
        Route::patch ('/employees/{id}',           [EmployeeController::class, 'update']);
        Route::delete('/employees/{id}',           [EmployeeController::class, 'destroy']);

        Route::patch ('/employees/{id}/contact',   [EmployeeController::class, 'saveContact']);
        Route::patch ('/employees/{id}/govt',      [EmployeeController::class, 'saveGovt']);
        Route::patch ('/employees/{id}/family',    [EmployeeController::class, 'saveFamily']);

        Route::get   ('/employees/{id}/education',                 [EmployeeEducationController::class, 'index']);
        Route::post  ('/employees/{id}/education',                 [EmployeeEducationController::class, 'store']);
        Route::patch ('/employees/{id}/education/{eduId}',         [EmployeeEducationController::class, 'update']);
        Route::delete('/employees/{id}/education/{eduId}',         [EmployeeEducationController::class, 'destroy']);

        Route::get   ('/employees/{id}/appointments',              [EmployeeAppointmentController::class, 'index']);
        Route::post  ('/employees/{id}/appointments',              [EmployeeAppointmentController::class, 'store']);
        Route::patch ('/employees/{id}/appointments/{apptId}',     [EmployeeAppointmentController::class, 'update']);
        Route::delete('/employees/{id}/appointments/{apptId}',     [EmployeeAppointmentController::class, 'destroy']);
    });



    Route::prefix('hrsi/leave')->group(function () {
        Route::get   ('/requests',                 [LeaveRequestController::class, 'index']);
        Route::post  ('/requests',                 [LeaveRequestController::class, 'store']);
        Route::get   ('/requests/{id}',            [LeaveRequestController::class, 'show'])->whereNumber('id');
        Route::patch ('/requests/{id}',            [LeaveRequestController::class, 'update'])->whereNumber('id');
        Route::delete('/requests/{id}',            [LeaveRequestController::class, 'destroy'])->whereNumber('id');
        Route::post  ('/requests/{id}/submit',     [LeaveRequestController::class, 'submit'])->whereNumber('id');
        Route::post  ('/requests/{id}/cancel',     [LeaveRequestController::class, 'cancel'])->whereNumber('id');

        Route::get   ('/approvals/inbox',          [LeaveApprovalController::class, 'inbox']);
        Route::post  ('/approvals/steps/{stepId}/approve', [LeaveApprovalController::class, 'approve'])->whereNumber('stepId');
        Route::post  ('/approvals/steps/{stepId}/reject',  [LeaveApprovalController::class, 'reject'])->whereNumber('stepId');

        Route::get   ('/posting/queue',            [LeavePostingController::class, 'queue']);
        Route::post  ('/posting/{requestId}',      [LeavePostingController::class, 'post'])->whereNumber('requestId');

        Route::get   ('/reports/employee/{empId}', [LeaveReportController::class, 'employeeSummary'])->whereNumber('empId');
        Route::get   ('/reports/office/{orgId}',   [LeaveReportController::class, 'officeSummary'])->whereNumber('orgId');
        Route::get   ('/reports/org',              [LeaveReportController::class, 'orgSummary']);
    });


    // ===== Asset Detail =====
    Route::get('/assets',        [AssetDetailController::class, 'index'])
        ->middleware('permission:fa.asset.view');

    Route::post('/assets',       [AssetDetailController::class, 'store'])
        ->middleware('permission:fa.asset.create');

    Route::get('/assets/{id}',   [AssetDetailController::class, 'show'])
        ->middleware('permission:fa.asset.view');

    Route::patch('/assets/{id}', [AssetDetailController::class, 'update'])
        ->middleware('permission:fa.asset.update');

    // Prefer soft delete (status → ARCHIVED). Add ?hard=1 to hard-delete.
    Route::delete('/assets/{id}',[AssetDetailController::class, 'destroy'])
        ->middleware('permission:fa.asset.delete');

    // Picture upload
    Route::post('/assets/{id}/picture', [AssetDetailController::class, 'uploadPicture'])
        ->middleware('permission:fa.asset.update');

    // Children (view only for now)
    Route::get('/assets/{id}/children', [AssetDetailController::class, 'children'])
        ->middleware('permission:fa.asset.view');

    // ===== Service Logs =====
    Route::get('/assets/{id}/service-logs',  [AssetServiceLogController::class, 'index'])
        ->middleware('permission:fa.maintenance.view');

    Route::post('/assets/{id}/service-logs', [AssetServiceLogController::class, 'store'])
        ->middleware('permission:fa.maintenance.create');

    Route::patch('/service-logs/{logId}',    [AssetServiceLogController::class, 'update'])
        ->middleware('permission:fa.maintenance.update');

    Route::delete('/service-logs/{logId}',   [AssetServiceLogController::class, 'destroy'])
        ->middleware('permission:fa.maintenance.update');








  });








/*
|--------------------------------------------------------------------------
| OPENU API under /open/api
|--------------------------------------------------------------------------
*/
Route::prefix('open/api')
    ->middleware(['web', 'session.bucket:open'])
    ->group(function () {
        Route::post('/login', [OpenUAuthController::class, 'login'])
            ->middleware('throttle:30,1')
            ->name('openu.login');

        Route::get('/me', function (Request $r) {
            return response()->json([
                'ok'   => true,
                'user' => $r->user()?->only(['id','name','email']),
            ]);
        })
            ->middleware(['auth:sanctum','app.access:OPENU'])
            ->name('openu.me');

        Route::post('/logout', [OpenUAuthController::class, 'logout'])
            ->middleware(['auth:sanctum','app.access:OPENU'])
            ->name('openu.logout');
    });

/*
|--------------------------------------------------------------------------
| SPA catch-all under /app — must come LAST
| Excludes /app/api/* and /app/lrwsis/* so it won't shadow API or CSRF routes
|--------------------------------------------------------------------------
*/
Route::get('/app', fn () => response()->file(public_path('app/index.html')));
Route::get('/app/{any}', fn () => response()->file(public_path('app/index.html')))
    ->where('any', '^(?!(api(?:/|$)|lrwsis/csrf-cookie$|broadcasting(?:/|$))).+');




