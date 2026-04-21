<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| USER ROLES (Pivot optional direct use)
|--------------------------------------------------------------------------
*/
class UserRole extends Model
{
    use HasFactory;

    protected $table = 'iam.user_roles';

    protected $fillable = [
        'user_id',
        'system_id',
        'role_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime'
    ];
}
