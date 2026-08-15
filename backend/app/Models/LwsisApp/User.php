<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

use App\Models\LwsisApp\Hris\HrEmployee;
use App\Models\LwsisApp\DeviceUserToken;

/*
|--------------------------------------------------------------------------
| USERS MODEL (Auth-ready)
|--------------------------------------------------------------------------
*/
class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'iam.users';

    protected $fillable = [
        'email',
        'password_hash',
        'user_type',
        'is_active',
        'name',
        'mobile_number',
        'company_id',
        'open_enabled',
        'biometrics_enabled',
        'location_enabled',
        'privacy_accepted',
        'privacy_accepted_at',
    ];

    protected $hidden = [
        'password_hash'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_enabled' => 'boolean',
        'biometrics_enabled' => 'boolean',
        'location_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'privacy_accepted' => 'boolean',
        'privacy_accepted_at' => 'datetime',
    ];

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class, 'user_id');
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'iam.user_roles', 'user_id', 'role_id')
            ->withPivot(['system_id', 'is_active'])
            ->withTimestamps();
    }

    public function systemAccess()
    {
        return $this->hasMany(UserSystemAccess::class, 'user_id');
    }
    public function employeeLink()
    {
        return $this->hasOne(UserEmployeeLink::class, 'user_id');
    }

    public function employee()
    {
        return $this->hasOneThrough(
            HrEmployee::class,
            UserEmployeeLink::class,
            'user_id',      // FK sa link table
            'id',           // PK sa employee
            'id',           // PK sa user
            'employee_id'   // FK sa link table papuntang employee
        );
    }
    public function deviceTokens()
{
    return $this->hasMany(DeviceUserToken::class, 'user_id');
}
}
