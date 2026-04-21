<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| USER SYSTEM ACCESS
|--------------------------------------------------------------------------
*/
class UserSystemAccess extends Model
{
    use HasFactory;

    protected $table = 'iam.user_system_access';

    protected $fillable = [
        'user_id',
        'system_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

