<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobile\UserLocationLog;
use App\Models\Mobile\UserGeofenceState;
use App\Models\Mobile\UserGeofenceEventLog;
use App\Models\Mobile\GeofenceZone;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LocationController extends Controller
{
    public function update(Request $request)
    {   
        // 🔐 Apple-review safe guard
        if (!auth()->check()) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Unauthenticated background call'
            ], 200);
        }

        $request->validate([
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'event'       => 'required|in:entered,exited',
            'zone'        => 'required|string|max:255',
            'occurred_at' => 'nullable|integer',
            'mocked'      => 'nullable|boolean',
        ]);

        $userId = auth()->id();

        try {

            /* ===============================
               1️⃣ Validate Zone Exists
            =============================== */
            $zone = GeofenceZone::where('name', $request->zone)->first();

            if (!$zone) {
                return response()->json([
                    'status' => 'ignored',
                    'reason' => 'Invalid zone'
                ], 200);
            }

            /* ===============================
               2️⃣ Prevent Duplicate Transition
            =============================== */
            $state = UserGeofenceState::firstOrNew([
                'user_id' => $userId,
                'zone_id' => $zone->id,
            ]);

            $newInside = $request->event === 'entered';

if ($state->exists && $state->inside === $newInside) {
    return response()->json([
        'status' => 'ignored',
        'reason' => 'Duplicate transition'
    ], 200);
}
            /* ===============================
               3️⃣ Update State
            =============================== */
            $state->fill([
                'inside'   => $request->event === 'entered',
                'last_lat' => $request->latitude,
                'last_lng' => $request->longitude,
            ])->save();

            /* ===============================
               4️⃣ Save Raw Log (audit trail)
            =============================== */
            UserLocationLog::create([
                'user_id'    => $userId,
                'latitude'   => $request->latitude,
                'longitude'  => $request->longitude,
                'event'      => $request->event,
                'zone'       => $zone->name,
                'mocked'     => $request->mocked ?? false,
                'occurred_at'=> $request->occurred_at
                    ? Carbon::createFromTimestampMs($request->occurred_at)
                    : now(),
            ]);

// /* ===============================
//    2️⃣ Load State
// =============================== */
// $state = UserGeofenceState::firstOrNew([
//     'user_id' => $userId,
//     'zone_id' => $zone->id,
// ]);

// $newInside = $request->event === 'entered';

// /* ===============================
//    3️⃣ Save Logs FIRST (Always)
// =============================== */
// UserLocationLog::create([
//     'user_id'    => $userId,
//     'latitude'   => $request->latitude,
//     'longitude'  => $request->longitude,
//     'event'      => $request->event,
//     'zone'       => $zone->name,
//     'mocked'     => $request->mocked ?? false,
//     'occurred_at'=> $request->occurred_at
//         ? Carbon::createFromTimestampMs($request->occurred_at)
//         : now(),
// ]);

// UserGeofenceEventLog::create([
//     'user_id'    => $userId,
//     'zone_name'  => $zone->name,
//     'event_type' => $request->event,
// ]);

// /* ===============================
//    4️⃣ Update State Only If Changed
// =============================== */
// if (!$state->exists || $state->inside !== $newInside) {

//     $state->fill([
//         'inside'   => $newInside,
//         'last_lat' => $request->latitude,
//         'last_lng' => $request->longitude,
//     ])->save();
// }

            /* ===============================
               5️⃣ Save Clean Event Log
            =============================== */
            UserGeofenceEventLog::create([
                'user_id'    => $userId,
                'zone_name'  => $zone->name,
                'event_type' => $request->event,
            ]);

        } catch (\Throwable $e) {

    Log::error('Geofence ERROR', [
        'user_id' => $userId,
        'message' => $e->getMessage(),
        'trace'   => $e->getTraceAsString(),
    ]);

    return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], 500);
}
        // catch (\Throwable $e) {

        //     // ❌ NEVER crash background call
        //     Log::warning('Geofence update skipped', [
        //         'user_id' => $userId,
        //         'error'   => $e->getMessage(),
        //     ]);

        //     return response()->json([
        //         'status' => 'ignored'
        //     ], 200);
        // }

        return response()->json(['status' => 'ok']);
    }
}