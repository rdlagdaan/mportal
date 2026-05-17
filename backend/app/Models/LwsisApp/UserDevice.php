<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $table = 'mobile.user_devices';

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'is_active',
    ];
}