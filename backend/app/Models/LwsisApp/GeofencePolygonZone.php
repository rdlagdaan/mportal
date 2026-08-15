<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;

class GeofencePolygonZone extends Model
{
    protected $table = 'mobile.geofence_polygon_zones';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function points()
    {
        return $this->hasMany(
            GeofencePolygonPoint::class,
            'zone_id'
        )->orderBy('point_order');
    }
}