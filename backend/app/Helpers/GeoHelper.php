<?php

if (! function_exists('distanceInMeters')) {
    function distanceInMeters($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) ** 2;

        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }
}
