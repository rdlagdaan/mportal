<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use App\Models\LwsisApp\GeofencePolygonZone;
use Illuminate\Http\JsonResponse;

class GeofencePolygonController extends Controller
{
    public function index(): JsonResponse
    {
        $zones = GeofencePolygonZone::query()
            ->where('is_active', true)
            ->with(['points' => function ($query) {
                $query->orderBy('point_order');
            }])
            ->orderBy('id')
            ->get();

        $data = $zones->map(function ($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,

                'coordinates' => $zone->points->map(function ($point) {
                    return [
                        'latitude' => (float) $point->latitude,
                        'longitude' => (float) $point->longitude,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}