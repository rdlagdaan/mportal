<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeWeeklyScheduleRequest extends Model
{
    protected $table = 'hris.hr_employee_weekly_schedule_requests';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'org_unit_id',
        'status',
        'remarks',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'approval_remarks',
        'is_active_pattern',
        'replaced_by_request_id',
        'created_at',
        'updated_at',
        'approved_by_user_id',
        'approved_by_employee_id',
        'rejected_by_user_id',
        'rejected_by_employee_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_active_pattern' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(HrOrgUnit::class, 'org_unit_id');
    }

    public function replacedByRequest()
    {
        return $this->belongsTo(HrEmployeeWeeklyScheduleRequest::class, 'replaced_by_request_id');
    }

    public function previousRequests()
    {
        return $this->hasMany(HrEmployeeWeeklyScheduleRequest::class, 'replaced_by_request_id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_user_id');
    }

    public function approvedByEmployee()
    {
        return $this->belongsTo(HrEmployee::class, 'approved_by_employee_id');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by_user_id');
    }

    public function rejectedByEmployee()
    {
        return $this->belongsTo(HrEmployee::class, 'rejected_by_employee_id');
    }

    public function days()
    {
        return $this->hasMany(HrEmployeeWeeklyScheduleRequestDay::class, 'request_id');
    }
}