<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Support\Geofence;
use App\Models\Mobile\GeofenceZone;
use App\Models\LwsisApp\User;

class UserLocationController extends Controller
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

    /* ============================================================
                        GEOFENCE CHECK
    ============================================================ */
    public function check(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'mocked'    => 'nullable|boolean',
        ]);

        try {
            $lat = (float) $request->latitude;
            $lng = (float) $request->longitude;

            $zone = GeofenceZone::where('name', 'Trinity University of Asia')->first();

            if (!$zone) {
                return response()->json(['error' => 'Geofence zone not found'], 500);
            }

            if (!$zone->center_lat || !$zone->center_lng || !$zone->radius) {
                return response()->json([
                    'error' => 'Geofence zone configuration incomplete'
                ], 500);
            }

            $result = Geofence::classify(
                $lat,
                $lng,
                (object)[
                    'center_lat' => (float) $zone->center_lat,
                    'center_lng' => (float) $zone->center_lng,
                ],
                (float) $zone->radius,
                (float) $zone->radius
            );

            return response()->json([
                'inside'   => $result['state'] === 'inside',
                'state'    => $result['state'],
                'distance' => $result['distance'],
                'zone'     => $zone->name,
            ]);

        } catch (\Throwable $e) {

            Log::error('Geofence check failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /* ============================================================
                        TOGGLE LOCATION
    ============================================================ */
    public function toggle(Request $request)
    {
        try {
            $request->validate([
                'enabled' => 'required|boolean',
            ]);

            $user = $this->getAuthUser($request);

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            // ✅ SIMPLE: no device validation anymore
            $user->location_enabled = $request->boolean('enabled');
            $user->save();

            return response()->json([
                'status' => 'success',
                'location_enabled' => $user->location_enabled,
            ]);

        } catch (\Throwable $e) {

            Log::error('Toggle location failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Toggle failed'
            ], 500);
        }
    }

    /* ============================================================
                        ZONES LIST
    ============================================================ */
    public function zones(Request $request)
    {
        try {
            $user = $this->getAuthUser($request);

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            return response()->json([
                'zones' => GeofenceZone::select(
                    'id',
                    'name',
                    'center_lat',
                    'center_lng',
                    'radius'
                )->get()
            ]);

        } catch (\Throwable $e) {

            Log::error('Geofence zones fetch failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch zones'
            ], 500);
        }
    }

      /* ============================================================
                        BACKGROUND UPDATE (🔥 FROM OLD)
    ============================================================ */
    public function update(Request $request)
    {
        $user = $this->getAuthUser($request);

        // 🔐 Do not crash background calls
        if (!$user) {
            return response()->json([
                'status' => 'ignored',
                'reason' => 'Unauthenticated'
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

        try {

            $zone = GeofenceZone::where('name', $request->zone)->first();

            if (!$zone) {
                return response()->json([
                    'status' => 'ignored',
                    'reason' => 'Invalid zone'
                ], 200);
            }

            /* ===============================
               STATE CHECK
            =============================== */
            $state = UserGeofenceState::firstOrNew([
                'user_id' => $user->id,
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
               UPDATE STATE
            =============================== */
            $state->fill([
                'inside'   => $newInside,
                'last_lat' => $request->latitude,
                'last_lng' => $request->longitude,
            ])->save();

            /* ===============================
               RAW LOG
            =============================== */
            UserLocationLog::create([
                'user_id'    => $user->id,
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
               CLEAN EVENT LOG
            =============================== */
            UserGeofenceEventLog::create([
                'user_id'    => $user->id,
                'zone_name'  => $zone->name,
                'event_type' => $request->event,
            ]);

        } catch (\Throwable $e) {

            Log::error('Geofence update failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error'
            ], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}