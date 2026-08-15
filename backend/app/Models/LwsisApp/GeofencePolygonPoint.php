<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;

class GeofencePolygonPoint extends Model
{
    protected $table = 'mobile.geofence_polygon_points';

    public $timestamps = false;

    protected $fillable = [
        'zone_id',
        'latitude',
        'longitude',
        'point_order',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'point_order' => 'integer',
    ];

    public function zone()
    {
        return $this->belongsTo(
            GeofencePolygonZone::class,
            'zone_id'
        );
    }
}