<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LwsisApp\Hris\EmployeeWeeklyScheduleRequest;

class ScheduleController extends Controller
{
    public function getMySchedule(Request $request)
    {
        try {
            $userId = $request->attributes->get('auth_user_id');

            if (!$userId) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            // 🔍 get employee_id
            $employeeLink = DB::table('iam.user_employee_links')
                ->where('user_id', $userId)
                ->first();

            if (!$employeeLink) {
                return response()->json([
                    'message' => 'Employee not found'
                ], 404);
            }

            $employeeId = $employeeLink->employee_id;

            // 🔍 get latest APPROVED schedule
            $schedule = EmployeeWeeklyScheduleRequest::with('details')
                ->where('employee_id', $employeeId)
                ->where('status', 'APPROVED')
                ->orderByDesc('submitted_at')
                ->first();

            if (!$schedule) {
                return response()->json([
                    'message' => 'No approved schedule'
                ], 404);
            }

            // 🔁 transform data
            $data = $schedule->details->map(function ($d) {
                return [
                    'day' => $this->getDayName($d->day_of_week),
                    'day_of_week' => $d->day_of_week,
                    'is_workday' => (bool) $d->is_workday,
                    'time_in' => $d->time_in,
                    'time_out' => $d->time_out,
                    'break_start' => $d->break_start,
                    'break_end' => $d->break_end,
                    'hours' => $this->computeHours(
                        $d->time_in,
                        $d->time_out,
                        $d->break_start,
                        $d->break_end
                    ),
                ];
            });

            return response()->json([
                'schedule' => $data
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function getDayName($day)
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ][$day] ?? 'Unknown';
    }

    private function computeHours($timeIn, $timeOut, $breakStart, $breakEnd)
    {
        if (!$timeIn || !$timeOut) return 0;

        $start = strtotime($timeIn);
        $end = strtotime($timeOut);

        $total = ($end - $start) / 3600;

        if ($breakStart && $breakEnd) {
            $break = (strtotime($breakEnd) - strtotime($breakStart)) / 3600;
            return round($total - $break, 2);
        }

        return round($total, 2);
    }
}