<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| ROLES
|--------------------------------------------------------------------------
*/
class Role extends Model
{
    use HasFactory;

    protected $table = 'iam.roles';

    protected $fillable = [
        'system_id',
        'code',
        'name',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'iam.role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }
}
