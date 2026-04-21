<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveApprovalChain extends Model
{
    protected $table = 'hris.hr_leave_approval_chains';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'leave_type_id',
        'employment_status_id',
        'employee_type_id',
        'function_type_id',
        'org_unit_id',
        'priority',
        'effective_from',
        'effective_to',
        'is_active',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'priority' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function steps()
    {
        return $this->hasMany(HrLeaveApprovalChainStep::class, 'chain_id')
                    ->orderBy('step_no');
    }

    public function leaveType()
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function employmentStatus()
    {
        return $this->belongsTo(HrEmploymentStatus::class, 'employment_status_id');
    }

    public function employeeType()
    {
        return $this->belongsTo(HrEmployeeType::class, 'employee_type_id');
    }

    public function functionType()
    {
        return $this->belongsTo(HrFunctionType::class, 'function_type_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(HrOrgUnit::class, 'org_unit_id');
    }
}