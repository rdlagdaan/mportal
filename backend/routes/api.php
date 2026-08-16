<?php

//v2

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MicrocredentialsApplicationController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\LwsisApp\AuthController;
use App\Http\Controllers\LwsisApp\EventControllerr;
use App\Http\Controllers\LwsisApp\ProfileController;
use App\Http\Controllers\LwsisApp\NotificationsController;
use App\Http\Controllers\LwsisApp\DeviceUserTokenController;
use App\Http\Controllers\LwsisApp\DailyTimeRecordController;
use App\Http\Controllers\LwsisApp\UserLocationController;
use App\Http\Controllers\LwsisApp\HrCollegesController;
use App\Http\Controllers\LwsisApp\HrOrgUnitsController;
use App\Http\Controllers\LwsisApp\IamUsersController;
use App\Http\Controllers\LwsisApp\ScheduleController;
use App\Http\Controllers\LwsisApp\UserGeofencingController;
use App\Http\Controllers\LwsisApp\admin\AdminUserDeviceController;
use App\Http\Controllers\LwsisApp\ICTOServiceDeskController;
use App\Http\Controllers\LwsisApp\KioskFaceController;
use App\Http\Controllers\LwsisApp\GeofencePolygonController;
use App\Http\Controllers\LwsisApp\admin\AdminDailyTimeRecordController;
use App\Http\Controllers\LwsisApp\VisitorVisitController;
use App\Http\Controllers\LwsisApp\VisitorKioskController;

use App\Http\Controllers\LwsisApp\VisitorAuthController;

// v2
Route::prefix('lwsis')->group(function () {

    // PUBLIC ROUTES
    Route::post('/login', [AuthController::class, 'login']);    
    Route::post('/biometrics-login', [AuthController::class, 'biometricLogin']);

    // VISITOR PUBLIC ROUTES
    Route::post(
        '/visitor/register',
        [VisitorAuthController::class, 'register']
    );

    Route::post(
        '/visitor/login',
        [VisitorAuthController::class, 'login']
    );


    // VISITOR PROTECTED ROUTES
    Route::middleware('auth:sanctum')
        ->prefix('visitor')
        ->group(function () {

            Route::get(
                '/me',
                [VisitorAuthController::class, 'me']
            );

            Route::put(
                '/profile',
                [VisitorAuthController::class, 'updateProfile']
            );

            Route::post(
                '/logout',
                [VisitorAuthController::class, 'logout']
            );

            // VISITOR VISIT REQUESTS
            Route::get(
                '/visits',
                [VisitorVisitController::class, 'index']
            );

            Route::post(
                '/visits',
                [VisitorVisitController::class, 'store']
            );

            Route::get(
                '/visits/{uuid}',
                [VisitorVisitController::class, 'show']
            );
        });

        // KIOSK VISITOR QR SCANNER
        Route::post(
            '/kiosk/visitor/scan',
            [VisitorKioskController::class, 'scan']
        );

    // ADMIN ROUTES
    Route::post('/send/notifications', [NotificationsController::class, 'store']);
    Route::get('/colleges', [HrCollegesController::class, 'getColleges']);
    Route::get('/org-units', [HrOrgUnitsController::class, 'getOrgUnits']);
    Route::get('/users', [IamUsersController::class, 'getAllUsers']);
    Route::get('/users/{id}', [IamUsersController::class, 'getUserById']);
    Route::post('/admin/users/{userId}/reset-device', [AdminUserDeviceController::class, 'resetDevice']);
    Route::get('/admin/users', [AdminUserDeviceController::class, 'getUsers']);
    Route::post('/device-check', [AuthController::class, 'deviceCheck']);
    Route::get('/admin/live-dtr',[AdminDailyTimeRecordController::class, 'index']);


    // PROTECTED ROUTES
    Route::middleware('api.token')->group(function () {
        Route::post('/biometrics-toggle', [AuthController::class, 'updateBiometrics']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/privacy/accept', [AuthController::class, 'acceptPrivacy']);

        Route::get('/me', [ProfileController::class, 'me']);

        // KIOSK FACE REGISTRATION
        Route::get(
            '/kiosk/face-status',
            [KioskFaceController::class, 'status']
        );

        Route::post(
            '/kiosk/face-enroll',
            [KioskFaceController::class, 'enroll']
        );

        Route::get('/notifications', [NotificationsController::class, 'index']);
        Route::get('/notifications/{id}', [NotificationsController::class, 'show']);
        Route::post('/notifications/{id}/read', [NotificationsController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationsController::class, 'markAllAsRead']);
        // Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount']);

       
        Route::get('/notifications/event/{eventId}', [NotificationsController::class, 'getEventDetails']);
    

        Route::post('/device-token', [DeviceUserTokenController::class, 'store']);
        Route::delete('/device-token', [DeviceUserTokenController::class, 'destroy']);



         Route::prefix('dtr')->group(function () {
            Route::post('/time-in', [DailyTimeRecordController::class, 'timeIn']);
            Route::post('/time-out', [DailyTimeRecordController::class, 'timeOut']);
            Route::get('/today', [DailyTimeRecordController::class, 'today']);
            Route::get('/monthly', [DailyTimeRecordController::class, 'monthly']);
        });

        Route::prefix('location')->group(function () {
            Route::post('/check', [UserLocationController::class, 'check']);
            Route::post('/toggle', [UserLocationController::class, 'toggle']);
            
            // OLD circle geofence
            Route::get('/zones', [UserLocationController::class, 'zones']);

            Route::post('/update', [UserLocationController::class, 'update']); 


            // NEW polygon geofence
    Route::get(
        '/polygon-zones',
        [GeofencePolygonController::class, 'index']
    );
        });


        Route::get('/my-schedule', [ScheduleController::class, 'getMySchedule']);

        Route::post('/geofencing/update', [UserGeofencingController::class, 'geofencingUpdate']);


        Route::get(
        '/icto/dashboard',
        [ICTOServiceDeskController::class, 'dashboard']
    );

    });

});
    Route::prefix('lwsis')
        ->middleware('api.token')
        ->group(function () {

            Route::get('/events', [EventControllerr::class, 'index']);
            Route::get('/events/{id}', [EventControllerr::class, 'show']);

        }
    );



//v1
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\MicrocredentialsApplicationController;
// use Illuminate\Support\Facades\Response;
// use Illuminate\Support\Facades\File;
// use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Api\Mobile\NotificationController;
use App\Http\Controllers\Api\Mobile\StudentGradeController;
use App\Http\Controllers\Api\Mobile\EnrollmentHistoryController;
use App\Http\Controllers\Api\Mobile\EventController;
use App\Http\Controllers\Api\Mobile\DeviceTokenController;
use App\Http\Controllers\Api\Mobile\StudentLedgerController;
use App\Http\Controllers\Auth\V1\LogoutController;
use App\Http\Controllers\Auth\V1\LoginController; 
use App\Http\Controllers\Api\Mobile\CollegeController;
use App\Http\Controllers\Api\Mobile\UserController;
use App\Http\Controllers\Api\Mobile\FaceRecognitionController;
use App\Http\Controllers\Api\Mobile\AppointmentController;
use App\Http\Controllers\Api\Mobile\AppointmentMessageController;
use App\Http\Controllers\Api\Mobile\UniversityOfficeController;
use App\Http\Controllers\Api\Mobile\TestNotificationController;

use App\Http\Controllers\Api\Mobile\ImportUserController;
use App\Http\Controllers\Api\Mobile\DtrController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1dtr/time-in', [DtrController::class, 'timeIn']);
    Route::post('/v1dtr/time-out', [DtrController::class, 'timeOut']);
    Route::get('/v1dtr/today', [DtrController::class, 'today']);
    Route::get('/v1dtr/month', [DtrController::class, 'monthly']);
});
Route::post('/import-users', [ImportUserController::class, 'import']);





// // Notification attachment route
// Route::get('/attachments/{path}', function ($path) {

//     $fullPath = base_path('storage/app/public/' . $path);

//     Log::info('ATTACHMENT DEBUG', [
//         'requested_path' => $path,
//         'resolved_path'  => $fullPath,
//         'exists'         => file_exists($fullPath),
//     ]);

//     abort_if(!file_exists($fullPath), 404);

//     return Response::file($fullPath);

// })->where('path', '.*');

// // Event Image route
// Route::get('/events/{path}', function ($path) {
//     $fullPath = base_path('storage/app/public/' . $path);

//     if (!file_exists($fullPath)) {
//         abort(404);
//     }

//     return response()->file($fullPath);
// })->where('path', '.*');




Route::get('/mobile-check', function () {
    return response()->json(['mobile' => true, 'env' => env('APP_ENV')]);
});

// Geofence check route
use App\Http\Controllers\Api\Mobile\GeofenceController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/location-toggle', [GeofenceController::class, 'toggleLocation']);
    Route::get('/geofence-zones', [GeofenceController::class, 'zones']);
});
Route::post('/geofence/check', [GeofenceController::class, 'check'])
    ->middleware('throttle:geofence');

use App\Http\Controllers\Api\Mobile\LocationController;
Route::middleware('auth:sanctum')->post(
    '/location/update',
    [LocationController::class, 'update']
);

// get offices
Route::get('/offices', [UniversityOfficeController::class, 'index']);

// testing notificatoin
Route::get('/test-notification', [TestNotificationController::class, 'send']);

    //  -------------------------------------- TEST ROUTE FOR FACESET DETAILS
    use Illuminate\Support\Facades\Http;
    Route::get('/test-faceset', function () {
        $apiKey = 'Xs4wVLU3d5k6ybCGR6Uqrm2VSywo3r14';
        $apiSecret = 'kzR0iIfSSfAkdR4-6ZpXIANzUhkE5-0V';
        $outerId = 'lrwsis_faceset';

        $response = Http::asForm()->post('https://api-us.faceplusplus.com/facepp/v3/faceset/getdetail', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'outer_id' => $outerId,
        ]);

        return $response->json();
    });

    Route::post('/face-register-local', [FaceRecognitionController::class, 'registerLocal']);
    Route::post('/face-login', [FaceRecognitionController::class, 'faceLogin']);
    Route::post('/face-remove', [FaceRecognitionController::class, 'removeFace']);

    // ------------------------------------- login / logout
    Route::post('/logout', [LogoutController::class, 'destroy']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/login/biometric', [LoginController::class, 'biometricLogin']);



    // -------------------------------------- NOTIFICATIONS API ROUTES
    // send notification
    Route::post('/notifications', [NotificationController::class, 'store']);
    //event notification
    Route::get('/notifications/event/{eventId}', [NotificationController::class, 'getEventDetails']);
    // get notification with middleware
    Route::middleware('auth:sanctum')->group(function () {
        // List all notifications for the logged-in user
         Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('notifications/{id}', [NotificationController::class, 'show']);
        // Mark single notification as read
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
       //remove Route::post('/events/{id}/read', [NotificationController::class, 'markEventAsRead']);
        // Mark all as read
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll']);

    });
    // -------------------------------------- device token API Routes
    //Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::middleware('auth:sanctum')->post('/device-tokens', [DeviceTokenController::class, 'store']);
    // -------------------------------------- enrollment history
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/enrollment-history', [EnrollmentHistoryController::class, 'index']);
    });
    // -------------------------------------- student grade
    Route::middleware('auth:sanctum')
        ->get('/student-grades/{student_number}/{sy}/{sem}', [StudentGradeController::class, 'index']);
    // -------------------------------------- event routes 
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);    // list all events
        Route::post('/', [EventController::class, 'store']);   // create new event
        Route::get('/{id}', [EventController::class, 'show']); // show single event
        Route::put('/{id}', [EventController::class, 'update']); // update event
        Route::delete('/{id}', [EventController::class, 'destroy']); // delete event
    });
    // -------------------------------------- college routes
    Route::get('/colleges', [CollegeController::class, 'getColleges']);
    // -------------------------------------- notification to college
    Route::post('/notifications/college', [NotificationController::class, 'sendToCollege']);
    Route::post('/notifications/office', [NotificationController::class, 'sendToOffice']);
    // -------------------------------------- event notifications
    Route::get('/send-event-notifications', [NotificationController::class, 'sendEventNotifications']);
    // -------------------------------------- save notifications
    Route::post('/save-notifications', [NotificationController::class, 'saveNotifications']);
    // -------------------------------------- student ledger routes
    Route::get('/student-ledger/{studentNumber}', [StudentLedgerController::class, 'show']);
    Route::get('/payments/{studentNumber}/{sy}/{sem}', [StudentLedgerController::class, 'payments']);
    Route::get('student-ledger-with-payments/{studentNumber}/{sy}/{sem}', [StudentLedgerController::class, 'showWithPayments']); 
    // -------------------------------------- user routes
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::get('/users/{id}', [UserController::class, 'getUserById']);
    Route::post('/user/biometrics-toggle', [UserController::class, 'updateBiometrics']);
    Route::get('/user/trusted-devices', [UserController::class, 'trustedDevices']);
    Route::post('/user/revoke-device/{id}', [UserController::class, 'revokeDevice']);
    

    // -------------------------------------- appointment routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/employees', [AppointmentController::class, 'getEmployees']); // 👈 Add this line
        Route::post('/appointments', [AppointmentController::class, 'book']);
        Route::get('/appointments', [AppointmentController::class, 'getAppointments']);
        Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
        Route::get('/appointments/{id}', [AppointmentController::class, 'getAppointmentById']);
        Route::get('/me', [LoginController::class, 'me']); 

    });
    // -------------------------------------- appointment messages routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/appointments/{appointment}/messages', [AppointmentMessageController::class, 'index']);
        Route::post('/appointments/{appointment}/messages', [AppointmentMessageController::class, 'store']);
    });




Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/microcredentials/apply', [MicrocredentialsApplicationController::class, 'store'])
    ->middleware('throttle:10,1'); // 10 requests/minute

Route::get('/health', fn() => response()->json(['ok'=>true]));



