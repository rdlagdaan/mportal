<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobile\UserLocationLog;
use App\Models\Mobile\UserGeofenceState;
use App\Models\Mobile\UserGeofenceEventLog;
use App\Models\Mobile\GeofenceZone;
use Illuminate\Support\Facades\Log;
use App\Models\LwsisApp\DeviceUserToken;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UserGeofencingController extends Controller
{
    public function geofencingUpdate(Request $request)
    {
        // 🔐 USE CUSTOM AUTH (IMPORTANT)
        $userId = $request->attributes->get('auth_user_id');

        if (!$userId) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Unauthenticated background call'
            ], 200);
        }

        // ✅ Validate request
        $request->validate([
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'event'       => 'required|in:entered,exited',
            'zone'        => 'required|string|max:255',
            'occurred_at' => 'nullable|integer',
            'mocked'      => 'nullable|boolean',
        ]);

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
               2️⃣ Anti-spam protection (10 sec)
            =============================== */
            $recent = UserGeofenceEventLog::where('user_id', $userId)
                ->where('zone_name', $zone->name)
                ->where('event_type', $request->event)
                ->where('created_at', '>=', now()->subSeconds(10))
                ->exists();

            if ($recent) {
                return response()->json([
                    'status' => 'ignored',
                    'reason' => 'Too frequent'
                ], 200);
            }

            /* ===============================
               3️⃣ Load / Create State
            =============================== */
            $state = UserGeofenceState::firstOrNew([
                'user_id' => $userId,
                'zone_id' => $zone->id,
            ]);

            $newInside = $request->event === 'entered';

            /* ===============================
               4️⃣ Prevent duplicate transition
            =============================== */
            if ($state->exists && $state->inside === $newInside) {
                return response()->json([
                    'status' => 'ignored',
                    'reason' => 'Duplicate transition'
                ], 200);
            }

            /* ===============================
               5️⃣ Update State
            =============================== */
            $state->fill([
                'inside'   => $newInside,
                'last_lat' => $request->latitude,
                'last_lng' => $request->longitude,
            ])->save();

            /* ===============================
               6️⃣ Save Raw Location Log
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

            /* ===============================
               7️⃣ Save Clean Event Log
            =============================== */
            UserGeofenceEventLog::create([
                'user_id'    => $userId,
                'zone_name'  => $zone->name,
                'event_type' => $request->event,
            ]);

            /* ===============================
               8️⃣ Mock location warning (optional)
            =============================== */
            if ($request->mocked) {
                Log::warning('⚠️ Mock location detected', [
                    'user_id' => $userId,
                    'zone'    => $zone->name,
                ]);
            }

        } catch (\Throwable $e) {

            Log::error('Geofence ERROR', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
            'zone'   => $zone->name,
            'event'  => $request->event,
        ]);
    }
}