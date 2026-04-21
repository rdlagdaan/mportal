<?php

namespace App\Services;

use App\Models\Mobile\UserGeofenceState;
use App\Models\Mobile\UserGeofenceEventLog;
use App\Models\Mobile\GeofenceZone;
use App\Services\PushNotificationService;

class GeofenceService
{

public function process(
    int $userId,
    float $lat,
    float $lng,
    string $event,
    ?int $occurredAt = null
): void {
    try {
        $zones = GeofenceZone::all();
        if ($zones->isEmpty()) {
            return;
        }

        foreach ($zones as $zone) {
            $state = UserGeofenceState::firstOrNew([
                'user_id' => $userId,
                'zone_id' => $zone->id,
            ]);

            // Initial baseline
            if (!$state->exists) {
                $state->fill([
                    'inside'     => $event === 'entered',
                    'last_lat'   => $lat,
                    'last_lng'   => $lng,
                    'updated_at' => now(),
                ])->save();
                continue;
            }

            // Ignore duplicates
            if (
                ($state->inside && $event === 'entered') ||
                (!$state->inside && $event === 'exited')
            ) {
                continue;
            }

            // Apply transition
            $state->fill([
                'inside'     => $event === 'entered',
                'last_lat'   => $lat,
                'last_lng'   => $lng,
                'updated_at' => now(),
            ])->save();

            UserGeofenceEventLog::create([
                'user_id'    => $userId,
                'zone_name'  => $zone->name,
                'event_type' => $event,
                'created_at' => now(),
            ]);
        }
    } catch (\Throwable $e) {
        // ✅ NEVER crash background / review devices
        \Log::warning('GeofenceService skipped', [
            'user_id' => $userId,
            'error'   => $e->getMessage(),
        ]);
    }
}


    /**
     * 📐 Haversine distance calculation
     */
    private function insideZone(
        float $lat,
        float $lng,
        float $centerLat,
        float $centerLng,
        int $radius
    ): bool {
        $earth = 6371000;

        $dLat = deg2rad($centerLat - $lat);
        $dLng = deg2rad($centerLng - $lng);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat)) *
             cos(deg2rad($centerLat)) *
             sin($dLng / 2) ** 2;

        return ($earth * 2 * atan2(sqrt($a), sqrt(1 - $a))) <= $radius;
    }
}

