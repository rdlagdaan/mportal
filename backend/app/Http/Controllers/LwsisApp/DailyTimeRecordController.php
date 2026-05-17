<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Mobile\DailyTimeRecord;

use App\Models\LwsisApp\User;

use App\Models\LwsisApp\Hris\HrEmployeeWeeklyScheduleRequest;
use App\Models\LwsisApp\Hris\HrLeaveRequest;

class DailyTimeRecordController extends Controller
{
    /* ============================================================
        AUTH HELPERS
    ============================================================ */

    private function getAuthUser(Request $request)
    {
        $userId =
            $request->attributes->get('auth_user_id');

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
        SCHEDULE HELPERS
    ============================================================ */

    private function getActiveSchedule($employeeId)
    {
        return HrEmployeeWeeklyScheduleRequest::with('days')
            ->where('employee_id', $employeeId)
            ->where('status', 'APPROVED')
            ->where('is_active_pattern', true)
            ->latest()
            ->first();
    }

    private function getTodaySchedule($schedule)
    {
        $today = now()->dayOfWeekIso;

        return $schedule->days
            ->where('day_of_week', $today)
            ->first();
    }

    private function validateScheduleOrFail($user)
    {
        $employeeId =
            $this->getEmployeeId($user->id);

        if (!$employeeId) {
            abort(response()->json([
                'message' =>
                    'Employee profile not found.'
            ], 404));
        }

        $schedule =
            $this->getActiveSchedule($employeeId);

        if (!$schedule) {
            abort(response()->json([
                'message' =>
                    'No approved schedule found.'
            ], 403));
        }

        $todaySchedule =
            $this->getTodaySchedule($schedule);

        if (
            !$todaySchedule ||
            !$todaySchedule->is_workday
        ) {
            abort(response()->json([
                'message' =>
                    'Today is a rest day.'
            ], 403));
        }

        return $todaySchedule;
    }

    /* ============================================================
        TIME IN
    ============================================================ */

    public function timeIn(Request $request)
    {
        $user =
            $this->getAuthUser($request);

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$this->ensureEmployee($user)) {
            return response()->json([
                'message' =>
                    'DTR is for employees only.'
            ], 403);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $this->validateScheduleOrFail($user);

        $today = Carbon::today();
        $now = Carbon::now();

        $dtr = DailyTimeRecord::firstOrCreate([
            'user_id' => $user->id,
            'work_date' => $today,
        ]);

        if ($dtr->time_in) {
            return response()->json([
                'message' =>
                    'Already timed in.'
            ], 409);
        }

        $dtr->update([
            
            'time_in' => $now,
            'time_in_lat' => $request->lat,
            'time_in_lng' => $request->lng,
        ]);

        $dtr->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Time in recorded.',
            'dtr' => $dtr,
        ]);
    }

    /* ============================================================
        TIME OUT
    ============================================================ */

    public function timeOut(Request $request)
    {
        $user =
            $this->getAuthUser($request);

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$this->ensureEmployee($user)) {
            return response()->json([
                'message' =>
                    'DTR is for employees only.'
            ], 403);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $today = Carbon::today();
        $now = Carbon::now();

        $dtr = DailyTimeRecord::where(
                'user_id',
                $user->id
            )
            ->whereDate(
                'work_date',
                $today
            )
            ->first();

        if (!$dtr || !$dtr->time_in) {
            return response()->json([
                'message' =>
                    'You must time in first.'
            ], 409);
        }

        if ($dtr->time_out) {
            return response()->json([
                'message' =>
                    'Already timed out.'
            ], 409);
        }

        $todaySchedule =
            $this->validateScheduleOrFail($user);

/*
|--------------------------------------------------------------------------
| PREVENT TOO EARLY TIME OUT
|--------------------------------------------------------------------------
*/

$in = Carbon::parse($dtr->time_in);

$workedHours =
    $in->diffInMinutes($now) / 60;

/*
|--------------------------------------------------------------------------
| REQUIRED HOURS
|--------------------------------------------------------------------------
*/

$requiredHours =
    $todaySchedule->required_hours
        ? (float) $todaySchedule->required_hours
        : 10;

/*
|--------------------------------------------------------------------------
| MUST COMPLETE AT LEAST HALF
|--------------------------------------------------------------------------
*/

$minimumHoursRequired =
    $requiredHours / 2;

/*
|--------------------------------------------------------------------------
| CHECK APPROVED UNDERTIME
|--------------------------------------------------------------------------
*/

$employeeId =
    $this->getEmployeeId($user->id);

$approvedUndertime =
    HrLeaveRequest::with('leaveType')
        ->where('employee_id', $employeeId)
        ->where('status', 'APPROVED')
        ->whereDate(
            'date_from',
            '<=',
            $today
        )
        ->whereDate(
            'date_to',
            '>=',
            $today
        )
        ->whereHas('leaveType', function ($q) {
            $q->whereRaw('LOWER(code) = ?', ['ut']);
        })
        ->latest() 
        ->first();

/*
|--------------------------------------------------------------------------
| BLOCK TOO EARLY TIME OUT
|--------------------------------------------------------------------------
| ONLY if NO approved undertime
|--------------------------------------------------------------------------
*/

if (
    !$approvedUndertime &&
    $workedHours <
    $minimumHoursRequired
) {

    return response()->json([
        'message' =>
            'You cannot time out yet. Minimum required hours not reached.',
        'worked_hours' =>
            round($workedHours, 2),
        'minimum_required_hours' =>
            round($minimumHoursRequired, 2),
    ], 403);
}

        $status =
            $this->computeAttendanceStatus(
                $user,
                $dtr->time_in,
                $now,
                $todaySchedule
            );

        $dtr->update([
            'time_out' => $now,
            'time_out_lat' => $request->lat,
            'time_out_lng' => $request->lng,
            'attendance_status' => $status,
        ]);

        $dtr->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Time out recorded.',
            'dtr' => $dtr,
        ]);
    }

    /* ============================================================
        ATTENDANCE STATUS
    ============================================================ */

    private function computeAttendanceStatus(
        $user,
        $timeIn,
        $timeOut,
        $scheduleDay
    ) {
        $in = Carbon::parse($timeIn);
        $out = Carbon::parse($timeOut);

        $workDate =
            $in->toDateString();

        /*
        |--------------------------------------------------------------------------
        | CHECK APPROVED LEAVE
        |--------------------------------------------------------------------------
        */

        $employeeId =
            $this->getEmployeeId($user->id);

        $approvedLeave =
            HrLeaveRequest::with('leaveType')
                ->where('employee_id', $employeeId)
                ->where('status', 'APPROVED')
                ->whereDate(
                    'date_from',
                    '<=',
                    $workDate
                )
                ->whereDate(
                    'date_to',
                    '>=',
                    $workDate
                )
                ->latest()
                ->first();

        /*
        |--------------------------------------------------------------------------
        | LEAVE FOUND
        |--------------------------------------------------------------------------
        */

        if (
            $approvedLeave &&
            $approvedLeave->leaveType
        ) {

            /*
            |--------------------------------------------------------------------------
            | UNDERTIME
            |--------------------------------------------------------------------------
            */

            if (
                strtolower(
                    $approvedLeave->leaveType->code
                ) === 'ut'
            ) {

                $scheduledIn =
                    $in->copy()
                        ->setTimeFromTimeString(
                            $scheduleDay->time_in
                        );

                $actualInMinutes =
                    ($in->hour * 60) +
                    $in->minute;

                $scheduledInMinutes =
                    ($scheduledIn->hour * 60) +
                    $scheduledIn->minute;

                if (
                    $actualInMinutes >
                    $scheduledInMinutes
                ) {
                    return 'late_undertime';
                }

                return 'undertime';
            }

            /*
            |--------------------------------------------------------------------------
            | USE LEAVE TYPE NAME
            |--------------------------------------------------------------------------
            */

            return strtolower(
                preg_replace(
                    '/[^a-zA-Z0-9]+/',
                    '_',
                    $approvedLeave->leaveType->name
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL ATTENDANCE
        |--------------------------------------------------------------------------
        */

        $scheduledIn =
            $in->copy()
                ->setTimeFromTimeString(
                    $scheduleDay->time_in
                );

        /*
        |--------------------------------------------------------------------------
        | WORKED HOURS
        |--------------------------------------------------------------------------
        */

        $workedHours =
            $in->diffInMinutes($out) / 60;

        /*
        |--------------------------------------------------------------------------
        | REQUIRED HOURS
        |--------------------------------------------------------------------------
        */

        $requiredHours =
            (float) $scheduleDay->required_hours;

        /*
        |--------------------------------------------------------------------------
        | HALF DAY
        |--------------------------------------------------------------------------
        */

        if (
            $workedHours <
            ($requiredHours / 2)
        ) {
            return 'half_day';
        }

        /*
        |--------------------------------------------------------------------------
        | LATE
        |--------------------------------------------------------------------------
        */

        $actualInMinutes =
            ($in->hour * 60) +
            $in->minute;

        $scheduledInMinutes =
            ($scheduledIn->hour * 60) +
            $scheduledIn->minute;

        if (
            $actualInMinutes >
            $scheduledInMinutes
        ) {
            return 'late';
        }

        return 'present';
    }

    /* ============================================================
        TODAY
    ============================================================ */

    public function today(Request $request)
    {
        $user =
            $this->getAuthUser($request);

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $dtr = DailyTimeRecord::where(
                'user_id',
                $user->id
            )
            ->whereDate(
                'work_date',
                Carbon::today()
            )
            ->first();

        $schedule =
            $this->getActiveSchedule(
                $this->getEmployeeId($user->id)
            );

        $todaySchedule =
            $schedule
                ? $this->getTodaySchedule($schedule)
                : null;

        return response()->json([
            'status' => 'success',

            'dtr' => $dtr,

            'has_schedule' => !!$schedule,

            'today_schedule' =>
                $todaySchedule
                    ? [
                        'is_workday' =>
                            $todaySchedule->is_workday,

                        'modality' =>
                            $todaySchedule->modality,

                        'required_hours' =>
                            $todaySchedule->required_hours,

                        'time_in' =>
                            $todaySchedule->time_in,

                        'time_out' =>
                            $todaySchedule->time_out,

                        'break_start' =>
                            $todaySchedule->break_start,

                        'break_end' =>
                            $todaySchedule->break_end,
                    ]
                    : null,
        ]);
    }

    /* ============================================================
        MONTHLY
    ============================================================ */

    public function monthly(Request $request)
    {
        $user =
            $this->getAuthUser($request);

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $year =
            $request->year ?? now()->year;

        $month =
            $request->month ?? now()->month;

        $records =
            DailyTimeRecord::where(
                    'user_id',
                    $user->id
                )
                ->whereYear(
                    'work_date',
                    $year
                )
                ->whereMonth(
                    'work_date',
                    $month
                )
                ->orderBy(
                    'work_date',
                    'desc'
                )
                ->get();

        return response()->json([
            'status' => 'success',
            'records' => $records,
        ]);
    }
}
