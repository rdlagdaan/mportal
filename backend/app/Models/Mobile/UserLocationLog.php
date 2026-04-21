<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;

class UserLocationLog extends Model
{
    protected $table = 'mobile.user_location_logs';

    public $timestamps = false; // 🔥 THIS MUST BE HERE

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'zone',
        'event',
        'mocked',
        'occurred_at',
    ];

    protected $casts = [
        'latitude'    => 'float',
        'longitude'   => 'float',
        'mocked'      => 'boolean',
        'occurred_at' => 'datetime',
    ];
}