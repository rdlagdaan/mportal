<?php

namespace App\Http\Controllers\LwsisApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LwsisApp\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IamUsersController extends Controller
{
    /**
     * 🔥 GET ALL USERS (ALIGNED WITH IAM + HRIS)
     */
    public function getAllUsers()
    {
        try {
            $users = User::with('employee')
                ->where('is_active', true)
                ->get()
                ->map(function ($user) {

                    $type = $user->user_type;
                    $identifier = null;

                    // 🔵 STUDENT
                    if ($type === 'student') {
                        $identifier = $user->email; // replace if you have student_no
                    }

                    // 🟢 EMPLOYEE
                    if ($type === 'employee' && $user->employee) {
                        $identifier = $user->employee->employee_no;
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name ?? 'Unknown',
                        'identifier' => $identifier,
                        'type' => $type,
                        'org_unit_id' => $user->employee->org_unit_id ?? null
                    ];
                });

            return response()->json($users);

        } catch (\Exception $e) {
            Log::error("Error fetching users: " . $e->getMessage());

            return response()->json([
                'error' => 'Failed to load users'
            ], 500);
        }
    }

    /**
     * 🔍 GET USER BY ID
     */
    public function getUserById($id)
    {
        try {
            $user = User::with('employee')->find($id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'employee_no' => $user->employee->employee_no ?? null,
                'org_unit_id' => $user->employee->org_unit_id ?? null,
                'biometrics_enabled' => (bool) $user->biometrics_enabled,
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching user: " . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch user'
            ], 500);
        }
    }

}