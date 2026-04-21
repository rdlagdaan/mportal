<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;

class UserGeofenceState extends Model
{
    protected $table = 'mobile.user_geofence_states';

    protected $fillable = [
        'user_id',
        'zone_id',
        'inside',
        'last_lat',
        'last_lng',
    ];

    protected $casts = [
        'user_id'  => 'integer',
        'zone_id'  => 'integer',
        'inside'   => 'boolean',
        'last_lat' => 'float',
        'last_lng' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships (Recommended)
    |--------------------------------------------------------------------------
    */

    public function zone()
    {
        return $this->belongsTo(GeofenceZone::class, 'zone_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}