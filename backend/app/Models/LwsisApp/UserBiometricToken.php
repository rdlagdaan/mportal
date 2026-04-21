<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;

class UserBiometricToken extends Model
{
    protected $table = 'mobile.user_biometric_tokens';

    protected $fillable = [
        'user_id',
        'biometric_token',
        'device_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}