<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveApprovalChainStep extends Model
{
    protected $table = 'hris.hr_leave_approval_chain_steps';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'chain_id',
        'step_no',
        'approver_kind',
        'dynamic_key',
        'role_code',
        'approver_employee_id',
        'is_required',
        'can_delegate',
        'is_active',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'step_no' => 'integer',
        'is_required' => 'boolean',
        'can_delegate' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function chain()
    {
        return $this->belongsTo(HrLeaveApprovalChain::class, 'chain_id');
    }

    public function approverEmployee()
    {
        return $this->belongsTo(HrEmployee::class, 'approver_employee_id');
    }
}