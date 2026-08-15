<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LwsisApp\Hris\HrEmployee;
use App\Models\LwsisApp\User;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        try {
            // 🔥 IMPORTANT FIX
            $userId = $request->attributes->get('auth_user_id');

            if (!$userId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $user = User::find($userId);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            /* ------------------------------------------ */
            /*                 EMPLOYEE                   */
            /* ------------------------------------------ */
            if ($user->user_type === 'employee') {

                $link = DB::table('iam.user_employee_links')
                    ->where('user_id', $user->id)
                    ->first();

                if (!$link) {
                    return response()->json([
                        'type' => 'employee',
                        'data' => null
                    ]);
                }

                $employee = HrEmployee::with([
    'activeOrgMembership.orgUnit',
])
->find($link->employee_id);

                $data = $employee ? $employee->toArray() : [];

                $data['org_unit'] =
    $employee?->activeOrgMembership?->orgUnit;

$data['photo_path'] =
    !empty($employee?->photo_path)
        ? url($employee->photo_path)
        : null;

$data['biometrics_enabled'] = $user->biometrics_enabled;
$data['location_enabled'] = $user->location_enabled;

                return response()->json([
                    'type' => 'employee',
                    'data' => $data
                ]);

                // return response()->json([
                //     'type' => 'employee',
                //     'data' => [
                //         ...$employee->toArray(),
                //         'biometrics_enabled' => $user->biometrics_enabled,
                //         'location_enabled' => $user->location_enabled,
                //     ]
                // ]);
            }

            /* ------------------------------------------ */
            /*                  STUDENT                   */
            /* ------------------------------------------ */
            if ($user->user_type === 'student') {
                return response()->json([
                    'type' => 'student',
                    'data' => [
                        'student_number' => '',
                        'course' => '',
                        'full_name' => $user->name,
                        'biometrics_enabled' => $user->biometrics_enabled,
                        'location_enabled' => $user->location_enabled,
                    ]
                ]);
            }

            return response()->json(['message' => 'Unknown user type'], 400);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}