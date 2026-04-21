<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;

class GeofenceZone extends Model
{
    protected $table = 'mobile.geofence_zones';

    protected $fillable = [
        'name',
        'center_lat',
        'center_lng',
        'radius',
    ];

    protected $casts = [
        'center_lat' => 'float',
        'center_lng' => 'float',
        'radius'     => 'integer',
    ];
}
