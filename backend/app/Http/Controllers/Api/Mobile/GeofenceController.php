<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use App\Support\Geofence;
use App\Models\Mobile\GeofenceZone;

class GeofenceController extends Controller
{
    /**
     * Check if coordinates are inside Trinity University of Asia zone
     */
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

            // Get Trinity zone
            $zone = GeofenceZone::where('name', 'Trinity University of Asia')->first();

            if (!$zone) {
                return response()->json([
                    'error' => 'Geofence zone not found'
                ], 500);
            }

            if (!$zone->center_lat || !$zone->center_lng || !$zone->radius) {
                return response()->json([
                    'error' => 'Geofence zone configuration incomplete'
                ], 500);
            }

            // Use helper classification
            $result = Geofence::classify(
                $lat,
                $lng,
                (object)[
                    'center_lat' => (float) $zone->center_lat,
                    'center_lng' => (float) $zone->center_lng,
                ],
                (float) $zone->radius, // enter radius
                (float) $zone->radius  // exit radius
            );

            $inside = $result['state'] === 'inside';

            return response()->json([
                'inside'   => $inside,
                'state'    => $result['state'],   // inside | outside | uncertain
                'distance' => $result['distance'],
                'zone'     => $zone->name,
            ]);

        } catch (\Throwable $e) {

            Log::error('Geofence check failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }
    public function toggleLocation(Request $request)
{
    try {

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // 🔐 Validate trusted device
        $rawDeviceToken = $request->header('X-Device-Auth');

        if (!$rawDeviceToken) {
            return response()->json([
                'message' => 'Device token missing.'
            ], 401);
        }

        $hashed = hash('sha256', $rawDeviceToken);

        $deviceValid = \App\Models\Mobile\UserDevice::where('user_id', $user->id)
            ->where('auth_token_hash', $hashed)
            ->where('is_revoked', false)
            ->exists();

        if (!$deviceValid) {
            return response()->json([
                'message' => 'Untrusted device.'
            ], 401);
        }

        // ✅ Update location setting
        $user->location_enabled = $request->boolean('enabled');
        $user->save();

        return response()->json([
            'status' => 'success',
            'location_enabled' => $user->location_enabled,
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'error' => 'Toggle failed',
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function zones(Request $request)
{
    try {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        return GeofenceZone::select(
            'id',
            'name',
            'center_lat',
            'center_lng',
            'radius'
        )->get();

    } catch (\Throwable $e) {

        \Log::error('Geofence zones fetch failed', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'error' => 'Failed to fetch zones'
        ], 500);
    }
}
}

