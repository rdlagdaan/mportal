<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveApprovalFlowStep extends Model
{
    protected $table = 'hris.hr_leave_approval_flow_steps';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'flow_set_id',
        'step_no',
        'approver_type',
        'role_id',
        'employee_id',
        'pool_code',
        'is_active',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'step_no' => 'integer',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function flowSet()
    {
        return $this->belongsTo(HrLeaveApprovalFlowSet::class, 'flow_set_id');
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}