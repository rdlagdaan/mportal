<?php

namespace App\Http\Controllers\Api\Mobile; 

use Illuminate\Http\Request;
use App\Models\Mobile\StudentProfile;
use App\Models\Mobile\EmployeeProfile;
use App\Http\Controllers\Controller;
use App\Models\Mobile\Users;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function getAllUsers()
{
    try {
        // Get all users with their linked identity
        $users = \App\Models\Mobile\UserIdentityLink::with('user')
            ->get()
            ->map(function ($item) {
                $user = $item->user;
                $identifier = $item->kind === 'student'
                    ? $item->student_number
                    : $item->employee_number;

                return [
                    'id' => $item->user_id,
                    'name' => $user->name ?? 'Unknown',
                    'identifier' => $identifier,
                    'type' => $item->kind
                ];
            });

        return response()->json($users);
    } catch (\Exception $e) {
        \Log::error("Error fetching users: " . $e->getMessage());
        return response()->json(['error' => 'Failed to load users'], 500);
    }
}

public function getUserById($id)
{
    try {
        // ✅ Include faceid_enabled in select()
        $user = \App\Models\Mobile\Users::select(
            'id',
            'name',
            'email',
            'face_token',
            'faceid_enabled'
        )->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'face_token' => $user->face_token,
            'has_face_token' => !empty($user->face_token),
            'faceid_enabled' => (bool) $user->faceid_enabled, // ✅ true/false cast
        ]);
    } catch (\Exception $e) {
        \Log::error("Error fetching user by ID: " . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch user'], 500);
    }
}


    // -------------------------------
    // Update Biometric Toggle (UPDATED)
    // -------------------------------

    public function updateBiometrics(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer',
        'enabled' => 'required|boolean',
    ]);

    $user = Users::find($request->user_id);

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // -----------------------------------------------------
    // 🔐 DEVICE VALIDATION - ONLY TRUSTED DEVICES CAN ENABLE BIOMETRICS
    // -----------------------------------------------------
    $rawDeviceToken = $request->header('X-Device-Auth');

    if (!$this->validateDevice($rawDeviceToken, $user->id)) {
        return response()->json([
            'error' => 'This device is not authorized to enable biometrics.'
        ], 401);
    }

    // -----------------------------------------------------
    // ENABLE BIOMETRICS
    // -----------------------------------------------------
    if ($request->enabled) {

        // Only generate a new remember_token when enabling
        $user->biometrics_enabled = true;
        $user->remember_token = Str::random(80);

    } 
    // -----------------------------------------------------
    // DISABLE BIOMETRICS
    // -----------------------------------------------------
    else {
        $user->biometrics_enabled = false;
        $user->remember_token = null;
    }

    $user->save();

    return response()->json([
        'status' => 'success',
        'biometrics_enabled' => $user->biometrics_enabled,
        'biometric_token' => $user->remember_token,
        'user' => $user,
    ]);
}

private function validateDevice(?string $rawToken, int $userId): bool
{
    if (!$rawToken) {
        return false;
    }

    // Hash the token exactly the same way it was stored
    $hashed = hash('sha256', $rawToken);

    // Look for matching device entry
    $device = \App\Models\Mobile\UserDevice::where('user_id', $userId)
        ->where('auth_token_hash', $hashed)
        ->where('is_revoked', false)
        ->first();

    if (!$device) {
        return false;
    }

    // Update last used timestamp
    $device->update([
        'last_used_at' => now(),
    ]);

    return true;
}

public function trustedDevices(Request $request)
{
    $devices = UserDevice::where('user_id', $request->user()->id)
        ->orderBy('last_used_at', 'desc')
        ->get();

    return response()->json($devices);
}


public function revokeDevice($id)
{
    $device = UserDevice::find($id);

    if (!$device) {
        return response()->json(['error' => 'Device not found'], 404);
    }

    $device->is_revoked = true;
    $device->save();

    return response()->json(['status' => 'revoked']);
}



// public function updateBiometrics(Request $request)
// {
//     $user = Users::find($request->user_id);

//     if (!$user) {
//         return response()->json(['error' => 'User not found'], 404);
//     }

//     // Enable biometrics
//     if ($request->enabled) {
//         $user->biometrics_enabled = true;

//         // 🔥 Generate NEW biometric login token
//         $user->remember_token = Str::random(80);
//     } 
//     // Disable biometrics
//     else {
//         $user->biometrics_enabled = false;

//         // 🔥 Remove token to disable biometric login
//         $user->remember_token = null;
//     }

//     $user->save();

//     return response()->json([
//         'status' => 'success',
//         'biometrics_enabled' => $user->biometrics_enabled,
//         'biometric_token' => $user->remember_token, // return so frontend can save
//         'user' => $user
//     ]);
// }


}