<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveRuleSet extends Model
{
    protected $table = 'hris.hr_leave_rule_sets';

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
        'approval_chain_id',
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

    public function items()
    {
        return $this->hasMany(HrLeaveRuleItem::class, 'rule_set_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function approvalChain()
    {
        return $this->belongsTo(HrLeaveApprovalChain::class, 'approval_chain_id');
    }
}