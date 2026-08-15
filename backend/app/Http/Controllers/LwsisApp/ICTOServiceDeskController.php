<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\LwsisApp\User;

class ICTOServiceDeskController extends Controller
{
    /* ============================================================
        AUTH HELPERS
    ============================================================ */

    private function getAuthUser(Request $request)
    {
        $userId = $request->attributes->get('auth_user_id');

        return $userId
            ? User::find($userId)
            : null;
    }

    private function ensureEmployee($user)
    {
        return $user &&
            $user->user_type === 'employee';
    }

    private function getEmployeeId($userId)
    {
        return DB::table('iam.user_employee_links')
            ->where('user_id', $userId)
            ->value('employee_id');
    }

    /* ============================================================
        DASHBOARD
    ============================================================ */

    public function dashboard(Request $request)
    {
        try {

            $user = $this->getAuthUser($request);

            if (!$this->ensureEmployee($user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee account required'
                ], 403);
            }

            $employeeId = $this->getEmployeeId($user->id);

            if (!$employeeId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            /* ====================================================
                DASHBOARD STATS
            ==================================================== */

            $stats = [
                'assigned' => DB::table('isd.requests')
                    ->where('requester_user_id', $user->id)
                    ->where('status', 'ASSIGNED')
                    ->count(),

                'in_progress' => DB::table('isd.requests')
                    ->where('requester_user_id', $user->id)
                    ->where('status', 'IN_PROGRESS')
                    ->count(),

                'waiting_requester' => DB::table('isd.requests')
                    ->where('requester_user_id', $user->id)
                    ->where('status', 'WAITING_REQUESTER')
                    ->count(),

                'resolved' => DB::table('isd.requests')
                    ->where('requester_user_id', $user->id)
                    ->where('status', 'RESOLVED')
                    ->count(),

                'closed' => DB::table('isd.requests')
                    ->where('requester_user_id', $user->id)
                    ->where('status', 'CLOSED')
                    ->count(),

                'cancelled' => DB::table('isd.requests')
                    ->where('requester_user_id', $user->id)
                    ->where('status', 'CANCELLED')
                    ->count(),
            ];

            /* ====================================================
                MY REQUESTS
            ==================================================== */

            $requests = DB::table('isd.requests as r')
                ->leftJoin(
                    'isd.services as s',
                    's.id',
                    '=',
                    'r.service_id'
                )
                ->where('r.requester_user_id', $user->id)
                ->select(
                    'r.id',
                    'r.request_no',
                    'r.title',
                    'r.description',
                    'r.status',
                    'r.severity',
                    'r.created_at',
                    's.name as service_name'
                )
                ->orderByDesc('r.created_at')
                ->limit(10)
                ->get();

            /* ====================================================
                OPEN REQUESTS
            ==================================================== */

            $openRequests = DB::table('isd.requests as r')
                ->leftJoin(
                    'isd.services as s',
                    's.id',
                    '=',
                    'r.service_id'
                )
                ->where('r.requester_user_id', $user->id)
                ->whereIn('r.status', [
                    'ASSIGNED',
                    'IN_PROGRESS',
                    'WAITING_REQUESTER',
                ])
                ->select(
                    'r.id',
                    'r.request_no',
                    'r.title',
                    'r.status',
                    'r.created_at',
                    's.name as service_name'
                )
                ->orderByDesc('r.created_at')
                ->limit(10)
                ->get();

            /* ====================================================
                NOTIFICATIONS
            ==================================================== */

            $notifications = DB::table('isd.notifications')
                ->where(function ($query) use ($user, $employeeId) {
                    $query->where('recipient_user_id', $user->id)
                          ->orWhere('recipient_employee_id', $employeeId);
                })
                ->select(
                    'id',
                    'request_id',
                    'notification_type',
                    'title',
                    'body',
                    'action_url',
                    'is_read',
                    'read_at',
                    'created_at'
                )
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            /* ====================================================
                SERVICE CATALOG
            ==================================================== */

            $services = DB::table('isd.services')
                ->where('is_active', true)
                ->select(
                    'id',
                    'code',
                    'name',
                    'description',
                    'default_severity'
                )
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',

                'stats' => $stats,

                'requests' => $requests,

                'open_requests' => $openRequests,

                'notifications' => $notifications,

                'services' => $services,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
?>
