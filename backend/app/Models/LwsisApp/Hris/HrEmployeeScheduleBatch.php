<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeScheduleBatch extends Model
{
    protected $table = 'hris.hr_employee_schedule_batches';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'org_unit_id',
        'period_start',
        'period_end',
        'status',
        'submitted_at',
        'submitted_by_user_id',
        'approved_at',
        'approved_by_user_id',
        'rejected_at',
        'rejected_by_user_id',
        'approval_remarks',
        'remarks',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(HrOrgUnit::class, 'org_unit_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by_user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_user_id');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by_user_id');
    }

    public function approvalLogs()
    {
        return $this->hasMany(HrEmployeeScheduleApprovalLog::class, 'batch_id');
    }

    public function details()
    {
        return $this->hasMany(HrEmployeeScheduleDetail::class, 'batch_id');
    }
}