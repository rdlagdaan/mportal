<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Mobile\DailyTimeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Mobile\GeofenceZone;

class DtrController extends Controller
{
    // ========== TIME IN ==========
    public function timeIn(Request $request)
    {
        $user = $request->user();

        // Employee only
        $kind = $user->identityLinks?->first()?->kind ?? null;
        if ($kind !== 'employee') {
            return response()->json([
                'message' => 'DTR is for employees only.'
            ], 403);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        // 🔥 Replace this with your real geofence validation
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
            'message' => 'Time in recorded.',
            'dtr' => $dtr
        ]);
    }

    // ========== TIME OUT ==========
    public function timeOut(Request $request)
    {
        $user = $request->user();

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

        $dtr->time_out = $now;
        $dtr->time_out_lat = $request->lat;
        $dtr->time_out_lng = $request->lng;

        $dtr->attendance_status = $this->computeStatus(
            $dtr->time_in,
            $dtr->time_out
        );
        

        $dtr->save();

        return response()->json([
            'message' => 'Time out recorded.',
            'dtr' => $dtr
        ]);
    }

    // ========== ATTENDANCE RULE ENGINE ==========
    private function computeStatus($timeIn, $timeOut)
    {
        $in = Carbon::parse($timeIn)->format('H:i:s');
        $out = Carbon::parse($timeOut)->format('H:i:s');

        // 1️⃣ Half Day if out <= 12:00
        if ($out <= '12:00:00') {
            return 'half_day';
        }

        // 2️⃣ Undertime if out between 3:00–4:59
        if ($out >= '15:00:00' && $out <= '16:59:59') {
            return 'undertime';
        }

        // 3️⃣ Half Day if in 12:00–1:10 AND out >= 5:00
        if ($in >= '12:00:00' && $in <= '13:10:00' && $out >= '17:00:00') {
            return 'half_day';
        }

        // 4️⃣ Late if in > 8:10
        if ($in > '08:10:00') {
            return 'late';
        }

        return 'present';
    }

    // 🔥 TEMP campus check (replace with your real geofence zone)
   
    private function isInsideCampus($lat, $lng)
    {
        return true; // For now allow — replace with real check
    }
    public function today(Request $request)
{
    $user = $request->user();

    $today = Carbon::today();

    $dtr = DailyTimeRecord::where('user_id', $user->id)
        ->whereDate('work_date', $today)
        ->first();

    return response()->json([
        'dtr' => $dtr
    ]);
}

public function monthly(Request $request)
{
    $user = $request->user();

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
        'records' => $records,
        'summary' => $summary
    ]);
}
private function distanceMeters($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371000;

    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(
        pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) *
        pow(sin($lonDelta / 2), 2)
    ));

    return $angle * $earthRadius;
}

}