<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceUserToken extends Model
{
    use HasFactory;

    protected $table = 'mobile.device_user_tokens';
    protected $primaryKey = 'id';

    public $timestamps = true; // because you have created_at, updated_at

    protected $fillable = [
        'user_id',
        'device_token',
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'device_token' => 'string',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}