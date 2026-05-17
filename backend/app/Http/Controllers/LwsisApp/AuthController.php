<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\LwsisApp\User;
use App\Models\LwsisApp\UserDevice;
use App\Models\LwsisApp\UserBiometricToken;

class AuthController extends Controller
{
    /* ============================================================
                        LOGIN
    ============================================================ */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'device_id' => 'required|string',
                'device_name' => 'nullable|string|max:255',
            ]);

            // 🔍 Find user
            $user = DB::table('iam.users')
                ->where('email', $request->email)
                ->where('is_active', true)
                ->first();

            if (!$user || !Hash::check($request->password, $user->password_hash)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // 🔐 DEVICE LOCK
            $deviceCheck = $this->checkOrRegisterDevice(
                $user->id,
                $request->device_id,
                $request->device_name
            );

            if ($deviceCheck !== true) {
                return $deviceCheck; // return error response
            }

            // 🔑 Generate token
            $plainToken = Str::random(60);

            DB::table('iam.api_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => bcrypt($plainToken),
                'token_sha256' => hash('sha256', $plainToken),
                'created_at' => now(),
            ]);

            // 👤 Employee profile
            $employeeId = DB::table('iam.user_employee_links')
                ->where('user_id', $user->id)
                ->value('employee_id');

            return response()->json([
                'status' => 'success',
                'token' => $plainToken,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'user_type' => $user->user_type,
                ],
                'profile' => [
                    'employee_id' => $employeeId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Login failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /* ============================================================
                    DEVICE CHECK / REGISTER
    ============================================================ */
    private function checkOrRegisterDevice($userId, $deviceId, $deviceName)
    {
        $existing = UserDevice::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->first();

        // ❌ Already locked to another device
        if ($existing && $existing->device_id !== $deviceId) {
            return response()->json([
                'status' => 'error',
                'message' => 'This account is already registered on another device. Please contact ICT.'
            ], 403);
        }

        // ✅ First-time login → register device
        if (!$existing) {
            UserDevice::create([
                'user_id' => $userId,
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'is_active' => true,
            ]);
        }

        return true;
    }

    /* ============================================================
                    BIOMETRIC TOGGLE
    ============================================================ */
    public function updateBiometrics(Request $request)
    {
        $userId = $request->attributes->get('auth_user_id');

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $user = User::find($userId);

        if (!$user || !$user->is_active){
            return response()->json([
                'status' => 'error',
                'message' => 'User inactive'
            ], 403);
        }

        try {

            if ($request->enabled) {

                $token = Str::random(80);

                UserBiometricToken::updateOrCreate(
                    ['user_id' => $user->id],
                    ['biometric_token' => $token]
                );

                $user->biometrics_enabled = true;
                $user->save();

                return response()->json([
                    'status' => 'success',
                    'biometrics_enabled' => true,
                    'biometric_token' => $token,
                ]);
            }

            // ❌ Disable
            UserBiometricToken::where('user_id', $user->id)->delete();

            $user->biometrics_enabled = false;
            $user->save();

            return response()->json([
                'status' => 'success',
                'biometrics_enabled' => false,
                'biometric_token' => null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update biometrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /* ============================================================
                    BIOMETRIC LOGIN
    ============================================================ */
    public function biometricLogin(Request $request)
    {
        $request->validate([
            'biometric_token' => 'required|string',
            'device_id' => 'required|string',
        ]);

        $record = UserBiometricToken::where('biometric_token', $request->biometric_token)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid biometric token'
            ], 401);
        }

        $user = DB::table('iam.users')
    ->where('id', $record->user_id)
    ->where('is_active', true)
    ->first();

        if (!$user || !$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'User inactive'
            ], 403);
        }

        // 🔐 Device check
        $deviceCheck = $this->checkOrRegisterDevice(
            $user->id,
            $request->device_id,
            null
        );

        if ($deviceCheck !== true) {
            return $deviceCheck;
        }

        // 🔑 Token
        $plainToken = Str::random(60);

        DB::table('iam.api_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => bcrypt($plainToken),
            'token_sha256' => hash('sha256', $plainToken),
            'created_at' => now(),
        ]);

        // 👤 Employee profile (same as login)
$employeeId = DB::table('iam.user_employee_links')
    ->where('user_id', $user->id)
    ->value('employee_id');

return response()->json([
    'status' => 'success',
    'token' => $plainToken,
    'user' => [
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->name,
        'user_type' => $user->user_type, // 🔥 REQUIRED
    ],
    'profile' => [
        'employee_id' => $employeeId
    ]
]);
    }

    /* ============================================================
                        LOGOUT
    ============================================================ */
    public function logout(Request $request)
    {
        try {
            $userId = $request->attributes->get('auth_user_id');

            if (!$userId) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            // ❗ DO NOT DELETE USER DEVICE (STRICT LOCK)
            // Only ICT can reset it manually

            // 🔥 Delete API tokens only
            DB::table('iam.api_tokens')
                ->where('user_id', $userId)
                ->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Logout failed'
            ], 500);
        }
    }

    /* ============================================================
                DEVICE CHECK (FOR FACE ID LOGIN)
============================================================ */
public function deviceCheck(Request $request)
{
    $request->validate([
        'device_id' => 'required|string',
    ]);

    try {
        // 🔍 Find active device
        $device = UserDevice::where('device_id', $request->device_id)
            ->where('is_active', true)
            ->latest()
            ->first();

        // ❌ Device not registered
        if (!$device) {
            return response()->json([
                'status' => 'success',
                'registered' => false,
                'message' => 'Device not registered'
            ]);
        }

        // 🔍 Get user
        $user = User::find($device->user_id);

        if (!$user || !$user->is_active) {
            return response()->json([
                'status' => 'error',
                'registered' => false,
                'message' => 'User inactive'
            ], 403);
        }

        // 🔍 Get biometric token (if exists)
        $biometric = UserBiometricToken::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'registered' => true,
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,

            // 🔐 Biometrics info
            'biometrics_enabled' => (bool) $user->biometrics_enabled,
            'biometric_token' => $biometric?->biometric_token, // null safe
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Device check failed',
            'message' => $e->getMessage()
        ], 500);
    }
}
}

// namespace App\Http\Controllers\LwsisApp;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Hash;
// use App\Models\LwsisApp\User;
// use App\Models\LwsisApp\UserEmployeeLink;
// use App\Models\LwsisApp\HrEmployee;
// use Illuminate\Support\Str;
// use Illuminate\Support\Facades\DB;
// use App\Models\LwsisApp\ApiToken;
// use App\Models\LwsisApp\UserBiometricToken;

// class AuthController extends Controller
// {
//     public function login(Request $request)
// {
//     try {
//         $request->validate([
//             'email' => 'required|email',
//             'password' => 'required'
//         ]);

//         // 🔍 Find user
//         $user = DB::table('iam.users')
//             ->where('email', $request->email)
//             ->where('is_active', true)
//             ->first();

//         if (!$user || !Hash::check($request->password, $user->password_hash)) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Invalid credentials'
//             ], 401);
//         }

//         // 🔍 Check type
//         $employeeId = null;

//         if ($user->user_type === 'employee') {
//             $employeeLink = DB::table('iam.user_employee_links')
//                 ->where('user_id', $user->id)
//                 ->first();

//             $employeeId = $employeeLink->employee_id ?? null;
//         }

//         // 🔑 Token
//         $plainToken = Str::random(60);
//         $tokenHash = hash('sha256', $plainToken);

//         DB::table('iam.api_tokens')->insert([
//             'user_id' => $user->id,
//             'token_hash' => bcrypt($plainToken),
//             'token_sha256' => $tokenHash,
//             'created_at' => now(),
//         ]);

//         return response()->json([
//             'status' => 'success',
//             'token' => $plainToken,
//             'user' => [
//                 'id' => $user->id,
//                 'email' => $user->email,
//                 'name' => $user->name,
//                 'user_type' => $user->user_type,
//             ],
//             'profile' => [
//                 'employee_id' => $employeeId
//             ]
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

//     /* ============================================================
//                     BIOMETRIC TOGGLE (ENABLE / DISABLE)
//     ============================================================ */
//     public function updateBiometrics(Request $request)
// {
//     $userId = $request->attributes->get('auth_user_id');

//     if (!$userId) {
//         return response()->json(['error' => 'Unauthorized'], 401);
//     }

//     $validated = $request->validate([
//         'enabled' => 'required|boolean',
//     ]);

//     $user = User::find($userId);

//     if (!$user) {
//         return response()->json(['error' => 'User not found'], 404);
//     }

//     // $rawDeviceToken = $request->header('X-Device-Auth');

//     // if (!$rawDeviceToken) {
//     //     return response()->json([
//     //         'error' => 'Missing device authentication token'
//     //     ], 400);
//     // }

//     // if (!$this->validateDevice($rawDeviceToken, $user->id)) {
//     //     return response()->json([
//     //         'error' => 'This device is not authorized'
//     //     ], 401);
//     // }

//     DB::beginTransaction();

//     try {

//         if ($validated['enabled']) {

//     $biometricToken = Str::random(80);

//     UserBiometricToken::updateOrCreate(
//         [
//             'user_id' => $user->id,
//         ],
//         [
//             'biometric_token' => $biometricToken
//         ]
//     );

//     $user->biometrics_enabled = true;
//     $user->save();

//     DB::commit();

//     return response()->json([
//         'status' => 'success',
//         'biometrics_enabled' => true,
//         'biometric_token' => $biometricToken,
//     ]);
// }
//         // if ($validated['enabled']) {

//         //     $biometricToken = Str::random(80);

//         //     UserBiometricToken::updateOrCreate(
//         //         [
//         //             'user_id' => $user->id,
//         //             'device_token' => $rawDeviceToken
//         //         ],
//         //         [
//         //             'biometric_token' => $biometricToken
//         //         ]
//         //     );

//         //     $user->biometrics_enabled = true;
//         //     $user->save();

//         //     DB::commit();

//         //     return response()->json([
//         //         'status' => 'success',
//         //         'biometrics_enabled' => true,
//         //         'biometric_token' => $biometricToken,
//         //     ]);
//         // }

//         // disable
//        // disable
//         UserBiometricToken::where('user_id', $user->id)->delete();

//         $user->biometrics_enabled = false;
//         $user->save();

//         DB::commit();

//         return response()->json([
//             'status' => 'success',
//             'biometrics_enabled' => false,
//             'biometric_token' => null,
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();

//         return response()->json([
//             'error' => 'Failed to update biometrics',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }

//     /* ============================================================
//                     DEVICE VALIDATION
//     ============================================================ */
//     protected function validateDevice(?string $rawDeviceToken, int $userId): bool
//     {
//         if (!$rawDeviceToken) {
//             return false;
//         }

//         return DB::table('mobile.device_user_tokens')
//             ->where('user_id', $userId)
//             ->where('device_token', $rawDeviceToken)
//             ->exists();
//     }

//     /* ============================================================
//                     BIOMETRIC LOGIN (FUTURE)
//     ============================================================ */
//    public function biometricLogin(Request $request)
// {
//     $request->validate([
//         'biometric_token' => 'required|string'
//     ]);

//     // 🔍 Find biometric record (NO DEVICE TOKEN)
//     $record = UserBiometricToken::where('biometric_token', $request->biometric_token)
//         ->first();

//     if (!$record) {
//         return response()->json([
//             'error' => 'Invalid biometric token'
//         ], 401);
//     }

//     // 🔍 Get user
//     $user = User::find($record->user_id);

//     if (!$user) {
//         return response()->json([
//             'error' => 'User not found'
//         ], 404);
//     }

//     if (!$user->is_active) {
//         return response()->json([
//             'error' => 'User inactive'
//         ], 403);
//     }

//     // 🔑 Generate API token
//     $plainToken = Str::random(60);
//     $tokenHash = hash('sha256', $plainToken);

//     DB::table('iam.api_tokens')->insert([
//         'user_id' => $user->id,
//         'token_hash' => bcrypt($plainToken),
//         'token_sha256' => $tokenHash,
//         'created_at' => now(),
//     ]);

//     return response()->json([
//         'status' => 'success',
//         'token' => $plainToken,
//         'user' => [
//             'id' => $user->id,
//             'email' => $user->email,
//             'name' => $user->name,
//         ]
//     ]);
// }

// public function logout(Request $request)
// {
//     try {

//         // ✅ Get user from your token middleware
//         $userId = $request->attributes->get('auth_user_id');

//         if (!$userId) {
//             return response()->json([
//                 'message' => 'Unauthorized'
//             ], 401);
//         }

//         // 1️⃣ Delete device tokens
//         $deletedDevices = \App\Models\Mobile\DeviceToken::where('user_id', $userId)->delete();

//         \Log::info('Deleted device tokens', [
//             'user_id' => $userId,
//             'deleted' => $deletedDevices
//         ]);

//         // 2️⃣ Delete API tokens (YOUR SYSTEM)
//         $deletedTokens = \DB::table('iam.api_tokens')
//             ->where('user_id', $userId)
//             ->delete();

//         \Log::info('Deleted API tokens', [
//             'user_id' => $userId,
//             'deleted' => $deletedTokens
//         ]);

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Logged out successfully'
//         ]);

//     } catch (\Throwable $e) {

//         \Log::error('Logout failed', [
//             'message' => $e->getMessage()
//         ]);

//         return response()->json([
//             'error' => 'Logout failed'
//         ], 500);
//     }
// }
// }