<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;

class UserGeofenceEventLog extends Model
{
    protected $table = 'mobile.user_geofence_event_logs';

    public $timestamps = false; // because you insert created_at manually

    protected $fillable = [
        'user_id',
        'zone_name',
        'event_type',
        'created_at',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'zone_name'  => 'string',
        'event_type' => 'string',
        'created_at' => 'datetime',
    ];
}
