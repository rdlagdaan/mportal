<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use App\Models\LwsisApp\Hris\HrEmployeeWeeklyScheduleRequest;
use App\Models\LwsisApp\Hris\HrLeaveRequest;
use App\Models\LwsisApp\User;
use App\Models\Mobile\DailyTimeRecord;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyTimeRecordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Geofence zone IDs
    |--------------------------------------------------------------------------
    |
    | Zone 1: Trinity University of Asia
    | Zone 2: Trinity Highschool Department
    |
    */

    private const TUA_ZONE_ID = 1;
    private const THS_ZONE_ID = 2;

    /*
    |--------------------------------------------------------------------------
    | Authentication helpers
    |--------------------------------------------------------------------------
    */

    private function getAuthUser(Request $request): ?User
    {
        $userId = $request->attributes->get('auth_user_id');

        return $userId
            ? User::find($userId)
            : null;
    }

    private function ensureEmployee(?User $user): bool
    {
        return $user !== null &&
            strtolower((string) $user->user_type) === 'employee';
    }

    private function getEmployeeId(int $userId): ?int
    {
        $employeeId = DB::table('iam.user_employee_links')
            ->where('user_id', $userId)
            ->value('employee_id');

        return $employeeId
            ? (int) $employeeId
            : null;
    }

    private function getEmployeeIdOrFail(User $user): int
    {
        $employeeId = $this->getEmployeeId($user->id);

        if (!$employeeId) {
            $this->fail(
                'Employee profile not found.',
                404
            );
        }

        return $employeeId;
    }

    private function validateAuthenticatedEmployee(
        Request $request
    ): array {
        $user = $this->getAuthUser($request);

        if (!$user) {
            $this->fail('Unauthorized.', 401);
        }

        if (!$this->ensureEmployee($user)) {
            $this->fail(
                'DTR is for employees only.',
                403
            );
        }

        $employeeId = $this->getEmployeeIdOrFail($user);

        return [
            'user' => $user,
            'employee_id' => $employeeId,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Response helper
    |--------------------------------------------------------------------------
    */

    private function fail(
        string $message,
        int $status,
        array $additionalData = []
    ): void {
        throw new HttpResponseException(
            response()->json(
                array_merge(
                    ['message' => $message],
                    $additionalData
                ),
                $status
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Schedule helpers
    |--------------------------------------------------------------------------
    */

    private function getActiveSchedule(
        int $employeeId
    ): ?HrEmployeeWeeklyScheduleRequest {
        return HrEmployeeWeeklyScheduleRequest::with('days')
            ->where('employee_id', $employeeId)
            ->where('status', 'APPROVED')
            ->where('is_active_pattern', true)
            ->latest()
            ->first();
    }

    private function getTodaySchedule(
        HrEmployeeWeeklyScheduleRequest $schedule
    ) {
        $dayOfWeek = now()->dayOfWeekIso;

        return $schedule->days
            ->firstWhere(
                'day_of_week',
                $dayOfWeek
            );
    }

    private function validateScheduleOrFail(
        int $employeeId
    ) {
        $schedule = $this->getActiveSchedule($employeeId);

        if (!$schedule) {
            $this->fail(
                'No approved schedule found.',
                403
            );
        }

        $todaySchedule = $this->getTodaySchedule($schedule);

        if (
            !$todaySchedule ||
            !$todaySchedule->is_workday
        ) {
            $this->fail(
                'Today is a rest day.',
                403
            );
        }

        return $todaySchedule;
    }

    private function isWorkFromHome($scheduleDay): bool
    {
        return strtoupper(
            trim((string) $scheduleDay->modality)
        ) === 'WFH';
    }

    /*
    |--------------------------------------------------------------------------
    | Organization and geofence helpers
    |--------------------------------------------------------------------------
    */

    private function getEmployeeOrgUnit(
        int $employeeId
    ): ?object {
        $today = Carbon::today()->toDateString();

        return DB::table(
            'hris.hr_org_unit_memberships as membership'
        )
            ->join(
                'hris.hr_org_units as unit',
                'unit.id',
                '=',
                'membership.org_unit_id'
            )
            ->where(
                'membership.employee_id',
                $employeeId
            )
            ->where(
                'membership.is_active',
                true
            )
            ->where(
                'membership.is_primary',
                true
            )
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull(
                        'membership.effective_from'
                    )
                    ->orWhereDate(
                        'membership.effective_from',
                        '<=',
                        $today
                    );
            })
            ->where(function ($query) use ($today) {
                $query
                    ->whereNull(
                        'membership.effective_to'
                    )
                    ->orWhereDate(
                        'membership.effective_to',
                        '>=',
                        $today
                    );
            })
            ->where(
                'unit.is_active',
                true
            )
            ->select([
                'unit.id',
                'unit.code',
                'unit.name',
            ])
            ->first();
    }

    private function getAssignedGeofence(object $orgUnit): object
{
    $orgUnitCode = strtoupper(trim((string) $orgUnit->code));

    $zoneId = $orgUnitCode === 'THS'
        ? self::THS_ZONE_ID
        : self::TUA_ZONE_ID;

    $zone = DB::table('mobile.geofence_zones')
        ->where('id', $zoneId)
        ->first();

    if (!$zone) {
        $this->fail(
            'Assigned geofence zone was not found.',
            500,
            [
                'org_unit_code' => $orgUnitCode,
                'zone_id' => $zoneId,
            ]
        );
    }

    // Normalize the property names
    $zone->latitude = (float) $zone->center_lat;
    $zone->longitude = (float) $zone->center_lng;

    return $zone;
}

    private function distanceInMeters(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2
    ): float {
        $earthRadius = 6371000;

        $latitudeDifference = deg2rad(
            $latitude2 - $latitude1
        );

        $longitudeDifference = deg2rad(
            $longitude2 - $longitude1
        );

        $a =
            sin($latitudeDifference / 2) ** 2 +
            cos(deg2rad($latitude1)) *
            cos(deg2rad($latitude2)) *
            sin($longitudeDifference / 2) ** 2;

        $c = 2 * atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

        return $earthRadius * $c;
    }

    private function validateEmployeeGeofenceOrFail(
        int $employeeId,
        float $latitude,
        float $longitude
    ): array {
        $orgUnit = $this->getEmployeeOrgUnit(
            $employeeId
        );

        if (!$orgUnit) {
            $this->fail(
                'Active primary organization assignment was not found.',
                404
            );
        }

        $zone = $this->getAssignedGeofence(
            $orgUnit
        );

        $zoneLatitude = (float) $zone->latitude;
        $zoneLongitude = (float) $zone->longitude;
        $zoneRadius = (float) $zone->radius;

        $distance = $this->distanceInMeters(
            $latitude,
            $longitude,
            $zoneLatitude,
            $zoneLongitude
        );

        if ($distance > $zoneRadius) {
            $this->fail(
                'You are outside your assigned campus geofence.',
                403,
                [
                    'org_unit_code' =>
                        strtoupper(
                            trim((string) $orgUnit->code)
                        ),

                    'org_unit_name' =>
                        $orgUnit->name,

                    'allowed_zone' =>
                        $zone->name,

                    'zone_id' =>
                        (int) $zone->id,

                    'distance_meters' =>
                        round($distance, 2),

                    'allowed_radius_meters' =>
                        $zoneRadius,
                ]
            );
        }

        return [
            'org_unit' => $orgUnit,
            'zone' => $zone,
            'distance' => $distance,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Time in
    |--------------------------------------------------------------------------
    */

    public function timeIn(Request $request)
    {
        $authenticated =
            $this->validateAuthenticatedEmployee(
                $request
            );

        /** @var User $user */
        $user = $authenticated['user'];

        $employeeId =
            $authenticated['employee_id'];

        $validated = $request->validate([
            'lat' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'lng' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ]);

        $latitude = (float) $validated['lat'];
        $longitude = (float) $validated['lng'];

        /*
        |--------------------------------------------------------------------------
        | Validate schedule first
        |--------------------------------------------------------------------------
        |
        | The schedule is checked before geofencing so that WFH employees may
        | time in from outside the physical campus geofence.
        |
        */

        $todaySchedule =
            $this->validateScheduleOrFail(
                $employeeId
            );

        $geofenceData = null;

        if (!$this->isWorkFromHome($todaySchedule)) {
            $geofenceData =
                $this->validateEmployeeGeofenceOrFail(
                    $employeeId,
                    $latitude,
                    $longitude
                );
        }

        $today = Carbon::today();
        $now = Carbon::now();

        $dtr = DB::transaction(function () use (
            $user,
            $today,
            $now,
            $latitude,
            $longitude
        ) {
            $existingDtr = DailyTimeRecord::where(
                'user_id',
                $user->id
            )
                ->whereDate(
                    'work_date',
                    $today
                )
                ->lockForUpdate()
                ->first();

            if (
                $existingDtr &&
                $existingDtr->time_in
            ) {
                $this->fail(
                    'Already timed in.',
                    409
                );
            }

            $dtr = $existingDtr ??
                new DailyTimeRecord();

            $dtr->user_id = $user->id;
            $dtr->work_date = $today;
            $dtr->time_in = $now;
            $dtr->time_in_lat = $latitude;
            $dtr->time_in_lng = $longitude;
            $dtr->save();

            return $dtr->fresh();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Time in recorded.',
            'modality' => $todaySchedule->modality,

            'org_unit_code' =>
                $geofenceData
                    ? strtoupper(
                        trim(
                            (string)
                            $geofenceData['org_unit']->code
                        )
                    )
                    : null,

            'geofence_zone' =>
                $geofenceData
                    ? [
                        'id' =>
                            (int)
                            $geofenceData['zone']->id,

                        'name' =>
                            $geofenceData['zone']->name,

                        'distance_meters' =>
                            round(
                                $geofenceData['distance'],
                                2
                            ),
                    ]
                    : null,

            'dtr' => $dtr,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Time out
    |--------------------------------------------------------------------------
    */

    public function timeOut(Request $request)
    {
        $authenticated =
            $this->validateAuthenticatedEmployee(
                $request
            );

        /** @var User $user */
        $user = $authenticated['user'];

        $employeeId =
            $authenticated['employee_id'];

        $validated = $request->validate([
            'lat' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'lng' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ]);

        $latitude = (float) $validated['lat'];
        $longitude = (float) $validated['lng'];

        /*
        |--------------------------------------------------------------------------
        | Validate schedule and assigned campus
        |--------------------------------------------------------------------------
        */

        $todaySchedule =
            $this->validateScheduleOrFail(
                $employeeId
            );

        $geofenceData = null;

        if (!$this->isWorkFromHome($todaySchedule)) {
            $geofenceData =
                $this->validateEmployeeGeofenceOrFail(
                    $employeeId,
                    $latitude,
                    $longitude
                );
        }

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

        if (
            !$dtr ||
            !$dtr->time_in
        ) {
            return response()->json([
                'message' =>
                    'You must time in first.',
            ], 409);
        }

        if ($dtr->time_out) {
            return response()->json([
                'message' =>
                    'Already timed out.',
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate worked and required hours
        |--------------------------------------------------------------------------
        */

        $timeIn = Carbon::parse(
            $dtr->time_in
        );

        $workedMinutes =
            $timeIn->diffInMinutes($now);

        $workedHours =
            $workedMinutes / 60;

        $requiredHours =
            $todaySchedule->required_hours
                ? (float)
                    $todaySchedule->required_hours
                : 10.0;

        $minimumHoursRequired =
            $requiredHours / 2;

        /*
        |--------------------------------------------------------------------------
        | Check approved undertime
        |--------------------------------------------------------------------------
        */

        $approvedUndertime =
            $this->getApprovedUndertime(
                $employeeId,
                $today
            );

        if (
            !$approvedUndertime &&
            $workedHours < $minimumHoursRequired
        ) {
            return response()->json([
                'message' =>
                    'You cannot time out yet. Minimum required hours not reached.',

                'worked_hours' =>
                    round($workedHours, 2),

                'minimum_required_hours' =>
                    round(
                        $minimumHoursRequired,
                        2
                    ),
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Compute attendance status
        |--------------------------------------------------------------------------
        */

        $attendanceStatus =
            $this->computeAttendanceStatus(
                $employeeId,
                $dtr->time_in,
                $now,
                $todaySchedule
            );

        $updatedDtr = DB::transaction(
            function () use (
                $user,
                $today,
                $now,
                $latitude,
                $longitude,
                $attendanceStatus
            ) {
                $lockedDtr =
                    DailyTimeRecord::where(
                        'user_id',
                        $user->id
                    )
                        ->whereDate(
                            'work_date',
                            $today
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    !$lockedDtr ||
                    !$lockedDtr->time_in
                ) {
                    $this->fail(
                        'You must time in first.',
                        409
                    );
                }

                if ($lockedDtr->time_out) {
                    $this->fail(
                        'Already timed out.',
                        409
                    );
                }

                $lockedDtr->time_out = $now;
                $lockedDtr->time_out_lat =
                    $latitude;
                $lockedDtr->time_out_lng =
                    $longitude;
                $lockedDtr->attendance_status =
                    $attendanceStatus;
                $lockedDtr->save();

                return $lockedDtr->fresh();
            }
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Time out recorded.',
            'modality' => $todaySchedule->modality,

            'org_unit_code' =>
                $geofenceData
                    ? strtoupper(
                        trim(
                            (string)
                            $geofenceData['org_unit']->code
                        )
                    )
                    : null,

            'geofence_zone' =>
                $geofenceData
                    ? [
                        'id' =>
                            (int)
                            $geofenceData['zone']->id,

                        'name' =>
                            $geofenceData['zone']->name,

                        'distance_meters' =>
                            round(
                                $geofenceData['distance'],
                                2
                            ),
                    ]
                    : null,

            'dtr' => $updatedDtr,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Leave helpers
    |--------------------------------------------------------------------------
    */

    private function getApprovedUndertime(
        int $employeeId,
        Carbon $date
    ): ?HrLeaveRequest {
        return HrLeaveRequest::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', 'APPROVED')
            ->whereDate(
                'date_from',
                '<=',
                $date
            )
            ->whereDate(
                'date_to',
                '>=',
                $date
            )
            ->whereHas(
                'leaveType',
                function ($query) {
                    $query->whereRaw(
                        'LOWER(code) = ?',
                        ['ut']
                    );
                }
            )
            ->latest()
            ->first();
    }

    private function getApprovedLeave(
        int $employeeId,
        string $date
    ): ?HrLeaveRequest {
        return HrLeaveRequest::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', 'APPROVED')
            ->whereDate(
                'date_from',
                '<=',
                $date
            )
            ->whereDate(
                'date_to',
                '>=',
                $date
            )
            ->latest()
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance status
    |--------------------------------------------------------------------------
    */

    private function computeAttendanceStatus(
        int $employeeId,
        $timeIn,
        $timeOut,
        $scheduleDay
    ): string {
        $in = Carbon::parse($timeIn);
        $out = Carbon::parse($timeOut);

        $workDate = $in->toDateString();

        $approvedLeave =
            $this->getApprovedLeave(
                $employeeId,
                $workDate
            );

        /*
        |--------------------------------------------------------------------------
        | Approved leave
        |--------------------------------------------------------------------------
        */

        if (
            $approvedLeave &&
            $approvedLeave->leaveType
        ) {
            $leaveCode = strtolower(
                trim(
                    (string)
                    $approvedLeave->leaveType->code
                )
            );

            if ($leaveCode === 'ut') {
                return $this->isLate(
                    $in,
                    $scheduleDay->time_in
                )
                    ? 'late_undertime'
                    : 'undertime';
            }

            return strtolower(
                trim(
                    preg_replace(
                        '/[^a-zA-Z0-9]+/',
                        '_',
                        (string)
                        $approvedLeave->leaveType->name
                    ),
                    '_'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Normal attendance
        |--------------------------------------------------------------------------
        */

        $workedHours =
            $in->diffInMinutes($out) / 60;

        $requiredHours =
            $scheduleDay->required_hours
                ? (float)
                    $scheduleDay->required_hours
                : 10.0;

        if (
            $workedHours <
            ($requiredHours / 2)
        ) {
            return 'half_day';
        }

        if (
            $this->isLate(
                $in,
                $scheduleDay->time_in
            )
        ) {
            return 'late';
        }

        return 'present';
    }

    private function isLate(
        Carbon $actualTimeIn,
        ?string $scheduledTimeIn
    ): bool {
        if (!$scheduledTimeIn) {
            return false;
        }

        $scheduled = $actualTimeIn
            ->copy()
            ->setTimeFromTimeString(
                $scheduledTimeIn
            );

        return $actualTimeIn->greaterThan(
            $scheduled
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Today
    |--------------------------------------------------------------------------
    */

    public function today(Request $request)
    {
        $authenticated =
            $this->validateAuthenticatedEmployee(
                $request
            );

        /** @var User $user */
        $user = $authenticated['user'];

        $employeeId =
            $authenticated['employee_id'];

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
                $employeeId
            );

        $todaySchedule = $schedule
            ? $this->getTodaySchedule($schedule)
            : null;

        $weeklySchedule = $schedule
            ? $schedule->days
                ->sortBy('day_of_week')
                ->map(function ($day) {
                    return [
                        'day_of_week' =>
                            (int)
                            $day->day_of_week,

                        'day_name' =>
                            $this->getDayName(
                                (int)
                                $day->day_of_week
                            ),

                        'is_workday' =>
                            (bool)
                            $day->is_workday,

                        'modality' =>
                            $day->modality,

                        'required_hours' =>
                            $day->required_hours,

                        'time_in' =>
                            $day->time_in,

                        'time_out' =>
                            $day->time_out,

                        'break_start' =>
                            $day->break_start,

                        'break_end' =>
                            $day->break_end,
                    ];
                })
                ->values()
            : collect();

        $orgUnit =
            $this->getEmployeeOrgUnit(
                $employeeId
            );

        $assignedZone = $orgUnit
            ? $this->getAssignedGeofence(
                $orgUnit
            )
            : null;

        return response()->json([
            'status' => 'success',

            'employee_id' => $employeeId,

            'org_unit' => $orgUnit
                ? [
                    'id' =>
                        (int) $orgUnit->id,

                    'code' =>
                        strtoupper(
                            trim(
                                (string)
                                $orgUnit->code
                            )
                        ),

                    'name' =>
                        $orgUnit->name,
                ]
                : null,

            'assigned_geofence' =>
    $assignedZone
        ? [
            'id' => (int) $assignedZone->id,
            'name' => $assignedZone->name,

            'latitude' => (float) $assignedZone->center_lat,
            'longitude' => (float) $assignedZone->center_lng,

            'radius' => (float) $assignedZone->radius,
        ]
        : null,

            'dtr' => $dtr,

            'has_schedule' =>
                $schedule !== null,

            'weekly_schedule' =>
                $weeklySchedule,

            'today_schedule' =>
                $todaySchedule
                    ? [
                        'is_workday' =>
                            (bool)
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

    private function getDayName(
        int $dayOfWeek
    ): string {
        return match ($dayOfWeek) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
            default => 'Unknown',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Monthly
    |--------------------------------------------------------------------------
    */

    public function monthly(Request $request)
    {
        $authenticated =
            $this->validateAuthenticatedEmployee(
                $request
            );

        /** @var User $user */
        $user = $authenticated['user'];

        $validated = $request->validate([
            'year' => [
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'month' => [
                'nullable',
                'integer',
                'between:1,12',
            ],
        ]);

        $year = isset($validated['year'])
            ? (int) $validated['year']
            : now()->year;

        $month = isset($validated['month'])
            ? (int) $validated['month']
            : now()->month;

        $records = DailyTimeRecord::where(
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
            'year' => $year,
            'month' => $month,
            'records' => $records,
        ]);
    }
}