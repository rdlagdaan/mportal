<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| ROLE PERMISSIONS (Pivot optional direct use)
|--------------------------------------------------------------------------
*/
class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'iam.role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id'
    ];
}
