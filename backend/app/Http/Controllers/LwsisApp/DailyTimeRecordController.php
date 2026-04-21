<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Mobile\DailyTimeRecord;
use App\Models\LwsisApp\User;

class DailyTimeRecordController extends Controller
{
    /* ============================================================
                        HELPER: AUTH USER
    ============================================================ */
    private function getAuthUser(Request $request)
    {
        $userId = $request->attributes->get('auth_user_id');

        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    private function ensureEmployee($user)
    {
        return $user && $user->user_type === 'employee';
    }

    /* ============================================================
                        TIME IN
    ============================================================ */
    public function timeIn(Request $request)
    {
        $user = $this->getAuthUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$this->ensureEmployee($user)) {
            return response()->json([
                'message' => 'DTR is for employees only.'
            ], 403);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if (!$this->isInsideCampus($request->lat, $request->lng)) {
            return response()->json([
                'message' => 'You must be inside your assigned campus to time in.'
            ], 403);
        }

        $today = Carbon::today();
        $now = Carbon::now();

        $dtr = DailyTimeRecord::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($dtr && $dtr->time_in) {
            return response()->json([
                'message' => 'Already timed in.'
            ], 409);
        }

        if (!$dtr) {
            $dtr = DailyTimeRecord::create([
                'user_id' => $user->id,
                'work_date' => $today,
            ]);
        }

        $dtr->update([
            'time_in' => $now,
            'time_in_lat' => $request->lat,
            'time_in_lng' => $request->lng,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Time in recorded.',
            'dtr' => $dtr
        ]);
    }

    /* ============================================================
                        TIME OUT
    ============================================================ */
    public function timeOut(Request $request)
    {
        $user = $this->getAuthUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$this->ensureEmployee($user)) {
            return response()->json([
                'message' => 'DTR is for employees only.'
            ], 403);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $today = Carbon::today();
        $now = Carbon::now();

        $dtr = DailyTimeRecord::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$dtr || !$dtr->time_in) {
            return response()->json([
                'message' => 'You must time in first.'
            ], 409);
        }

        if ($dtr->time_out) {
            return response()->json([
                'message' => 'Already timed out.'
            ], 409);
        }

        if (!$this->isInsideCampus($request->lat, $request->lng)) {
            return response()->json([
                'message' => 'You must be inside your assigned campus to time out.'
            ], 403);
        }

        $dtr->update([
            'time_out' => $now,
            'time_out_lat' => $request->lat,
            'time_out_lng' => $request->lng,
            'attendance_status' => $this->computeStatus(
                $dtr->time_in,
                $now
            )
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Time out recorded.',
            'dtr' => $dtr
        ]);
    }

    /* ============================================================
                        TODAY RECORD
    ============================================================ */
    public function today(Request $request)
    {
        $user = $this->getAuthUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $today = Carbon::today();

        $dtr = DailyTimeRecord::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        return response()->json([
            'status' => 'success',
            'dtr' => $dtr
        ]);
    }

    /* ============================================================
                        MONTHLY RECORDS
    ============================================================ */
    public function monthly(Request $request)
    {
        $user = $this->getAuthUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;

        $records = DailyTimeRecord::where('user_id', $user->id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->orderBy('work_date', 'desc')
            ->get();

        $summary = [
            'present' => $records->where('attendance_status', 'present')->count(),
            'late' => $records->where('attendance_status', 'late')->count(),
            'half_day' => $records->where('attendance_status', 'half_day')->count(),
            'undertime' => $records->where('attendance_status', 'undertime')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'records' => $records,
            'summary' => $summary
        ]);
    }

    /* ============================================================
                        ATTENDANCE RULE ENGINE
    ============================================================ */
    private function computeStatus($timeIn, $timeOut)
    {
        $in = Carbon::parse($timeIn)->format('H:i:s');
        $out = Carbon::parse($timeOut)->format('H:i:s');

        if ($out <= '12:00:00') {
            return 'half_day';
        }

        if ($out >= '15:00:00' && $out <= '16:59:59') {
            return 'undertime';
        }

        if ($in >= '12:00:00' && $in <= '13:10:00' && $out >= '17:00:00') {
            return 'half_day';
        }

        if ($in > '08:10:00') {
            return 'late';
        }

        return 'present';
    }

    /* ============================================================
                        GEOFENCE (TEMP)
    ============================================================ */
    private function isInsideCampus($lat, $lng)
    {
        return true; // Replace with real geofence logic
    }
}