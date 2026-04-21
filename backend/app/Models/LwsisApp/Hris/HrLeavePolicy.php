<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeavePolicy extends Model
{
    protected $table = 'hris.hr_leave_policies';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'leave_type_id',
        'employment_status_id',
        'employee_type_id',
        'function_type_id',
        'max_per_year',
        'carry_forward_allowed',
        'max_carry_forward',
        'effective_from',
        'effective_to',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'max_per_year' => 'decimal:2',
        'max_carry_forward' => 'decimal:2',
        'carry_forward_allowed' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

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
}