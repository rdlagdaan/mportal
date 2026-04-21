<?php

namespace App\Http\Controllers\Auth\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Models
use App\Models\Mobile\Users;
use App\Models\Mobile\StudentProfile;
use App\Models\Mobile\EnrollmentHistory;
use App\Models\Mobile\EmployeeProfile;
use App\Models\Mobile\UserDevice;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    // -----------------------------------------------------------
    // Helper: Load all relations + student/employee details
    // -----------------------------------------------------------
    private function loadFullUserData($user)
    {
        $relations = ['identityLinks', 'roles', 'userType'];
        $user->load($relations);

        $identity = $user->identityLinks?->first();
        $kind     = $identity?->kind ?? null;

        $studentProfile     = null;
        $enrollmentHistory  = [];
        $employeeProfile    = null;

        if ($kind === 'student' && $identity?->student_number) {
            $sn = $identity->student_number;
            $studentProfile = StudentProfile::where('student_number', $sn)->first();
            $enrollmentHistory = EnrollmentHistory::where('student_number', $sn)
                ->orderBy('created_at', 'desc')->get();
        }

        if ($kind === 'employee' && $identity?->employee_number) {
            $en = $identity->employee_number;
            $employeeProfile = EmployeeProfile::where('employee_number', $en)->first();
        }

        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'mobile'   => $user->mobile_number,

            'location_enabled' => $user->location_enabled,
            'biometrics_enabled' => $user->biometrics_enabled,
            'remember_token'     => $user->remember_token,

            'faceid_enabled' => $user->faceid_enabled,
            'faceid_id'      => $user->faceid_id,

            'user_type' => $user->userType?->name,
            'roles'     => $user->roles?->pluck('role_id') ?? [],

            'identities' => $user->identityLinks
                ? $user->identityLinks->map(fn($link) => [
                    'kind'            => $link->kind,
                    'student_number'  => $link->student_number,
                    'employee_number' => $link->employee_number,
                    'guest_number'    => $link->guest_number,
                ])
                : [],

            'student_profile'    => $studentProfile,
            'enrollment_history' => $enrollmentHistory,
            'employee_profile'   => $employeeProfile,
        ];
    }

    // -----------------------------------------------------------
    // EMAIL + PASSWORD LOGIN  (REGISTER / UPDATE DEVICE)
    // -----------------------------------------------------------
    public function login(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'password'     => 'required|string',
            'device_id'    => 'nullable|string',
            'device_model' => 'nullable|string',
            'device_os'    => 'nullable|string',
        ]);

        // 🔎 Find user
        $user = Users::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Load relations
        $user->load(['identityLinks', 'roles', 'userType']);

        // Generate Sanctum access token
        $token = $user->createToken('auth_token')->plainTextToken;

        // -----------------------------------------------------------
        // DEVICE TRUST TOKEN GENERATE / UPDATE
        // -----------------------------------------------------------
        $rawDeviceToken = Str::random(80);
        $hashedToken    = hash('sha256', $rawDeviceToken);

        $existingDevice = UserDevice::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->first();

        if ($existingDevice) {
            $existingDevice->update([
                'device_model'    => $request->device_model,
                'device_os'       => $request->device_os,
                'auth_token_hash' => $hashedToken,
                'ip_address'      => $request->ip(),
                'last_used_at'    => now(),
            ]);
        } else {
            UserDevice::create([
                'user_id'         => $user->id,
                'device_id'       => $request->device_id,
                'device_model'    => $request->device_model,
                'device_os'       => $request->device_os,
                'auth_token_hash' => $hashedToken,
                'ip_address'      => $request->ip(),
                'last_used_at'    => now(),
            ]);
        }

        // Prepare user data
        $fullUser = $this->loadFullUserData($user);

        return response()->json([
            'status'            => 'success',
            'message'           => 'Login successful',
            'token'             => $token,
            'device_auth_token' => $rawDeviceToken,
            'user'              => $fullUser,
        ]);
    }

    // -----------------------------------------------------------
    // BIOMETRIC LOGIN
    // -----------------------------------------------------------
    public function biometricLogin(Request $request)
    {
        $request->validate([
            'email'           => 'required|email',
            'biometric_token' => 'required|string',
        ]);

        // Find user
        $user = Users::where('email', $request->email)->first();
        if (!$user) return response()->json(['error' => 'User not found'], 404);

        if (!$user->biometrics_enabled)
            return response()->json(['error' => 'Biometric login disabled'], 403);

        if ($user->remember_token !== $request->biometric_token)
            return response()->json(['error' => 'Invalid biometric token'], 401);

        // Check device trust
        $rawDeviceToken = $request->header('X-Device-Auth');
        if (!$this->validateDevice($rawDeviceToken, $user->id))
            return response()->json(['error' => 'Device not recognized'], 401);

        $user->load(['identityLinks', 'roles', 'userType']);

        // Create new Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        $fullUser = $this->loadFullUserData($user);

        return response()->json([
            'status'            => 'success',
            'message'           => 'Biometric login successful',
            'token'             => $token,
            'device_auth_token' => $rawDeviceToken,
            'user'              => $fullUser,
        ]);
    }

    // -----------------------------------------------------------
    // DEVICE VALIDATION
    // -----------------------------------------------------------
    private function validateDevice($rawToken, $userId)
    {
        if (!$rawToken) return false;

        $hashed = hash('sha256', $rawToken);

        return UserDevice::where('user_id', $userId)
            ->where('auth_token_hash', $hashed)
            ->where('is_revoked', false)
            ->exists();
    }

    // -----------------------------------------------------------
    // /api/me — Restore session
    // -----------------------------------------------------------
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['error' => 'Unauthenticated'], 401);

            $fullUser = $this->loadFullUserData($user);

            return response()->json([
                'status' => 'success',
                'user'   => $fullUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error'
            ], 500);
            // return response()->json([
            //     'error'   => 'ME ERROR',
            //     'message' => $e->getMessage(),
            //     'line'    => $e->getLine(),
            //     'file'    => $e->getFile(),
            // ], 500);
        }
    }

    // -----------------------------------------------------------
    // LOGOUT
    // -----------------------------------------------------------
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully',
        ]);
    }
}