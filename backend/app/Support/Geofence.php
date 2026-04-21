<?php

namespace App\Support;

class Geofence
{
    public static function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }

    /**
     * Life360-like classification using hysteresis.
     * Returns: "inside", "outside", or "uncertain" (near boundary)
     */
    public static function classify(float $lat, float $lng, object $zone, float $enterRadius, float $exitRadius): array
    {
        $d = self::distanceInMeters($lat, $lng, $zone->center_lat, $zone->center_lng);

        if ($d <= $enterRadius) return ['state' => 'inside', 'distance' => $d];
        if ($d >= $exitRadius)  return ['state' => 'outside', 'distance' => $d];

        return ['state' => 'uncertain', 'distance' => $d];
    }
}
