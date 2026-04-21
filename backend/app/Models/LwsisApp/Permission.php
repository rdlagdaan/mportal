<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
class Permission extends Model
{
    use HasFactory;

    protected $table = 'iam.permissions';

    protected $fillable = [
        'system_id',
        'name',
        'title',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'iam.role_permissions', 'permission_id', 'role_id');
    }
}
