<?php

namespace App\Models\Mobile;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Mobile\FaceId;
use App\Models\Mobile\UserDailyUsageLog;

class Users extends Authenticatable
{
   use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'public.users';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'password',
        'micro_enabled',
        'lrwsis_enabled',
        'biometrics_enabled',
        'remember_token',
        'company_id',        // ✅ ADD THIS
    'open_enabled',      // ✅ ADD THIS
    'location_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        //'face_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'micro_enabled' => 'boolean',
        'lrwsis_enabled' => 'boolean',
        'location_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // 'faceid_enabled' => 'boolean',
        
        'biometrics_enabled'  => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    // In User.php
public function identityLinks()
{
    return $this->hasMany(UserIdentityLink::class, 'user_id');
}

public function roles()
{
    return $this->hasMany(UserRole::class, 'user_id');
}

public function userType()
{
    return $this->belongsTo(UserType::class, 'user_type_id');
}
public function studentProfile()
{
    return $this->hasOne(StudentProfile::class, 'student_number', 'student_number');
}
public function employeeProfile()
{
    return $this->hasOne(StudentProfile::class, 'employee_number', 'employee_number');
}
public function enrollmentHistories()
{
    return $this->hasMany(EnrollmentHistory::class, 'student_number', 'student_number');
}
public function markAppOpen(): UserDailyUsageLog
{
    return UserDailyUsageLog::markOpen($this->id);
}

// public function faceid()
// {
//     return $this->belongsTo(FaceId::class, 'faceid_id', 'id')->withDefault();
// }

 /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function dailyUsageLogs()
    {
        return $this->hasMany(
            UserDailyUsageLog::class,
            'user_id',
            'id'
        );
    }

    /**
     * Convenience: today's usage log
     */
    public function todayUsage()
    {
        return $this->hasOne(
            UserDailyUsageLog::class,
            'user_id',
            'id'
        )->whereDate('date', now()->toDateString());
    }



// Optional: helper function to get number
public function getNumberAttribute()
{
    if ($this->studentProfile) {
        return $this->studentProfile->student_number;
    } elseif ($this->employeeProfile) {
        return $this->employeeProfile->employee_number;
    }

    return null;
}




}