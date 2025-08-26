<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAppAccess extends Model
{
    protected $table = 'user_app_access';

    protected $fillable = [
        'user_id',
        'app_id',
        'is_enabled',
    ];

    public function app()
    {
        return $this->belongsTo(App::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
