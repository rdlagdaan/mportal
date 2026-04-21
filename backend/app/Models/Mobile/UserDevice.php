<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $table = 'public.user_devices';

    protected $fillable = [
        'user_id',
        'device_id',
        'device_model',
        'device_os',
        'auth_token_hash',
        'last_used_at',
        'ip_address',
        'is_revoked'
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}