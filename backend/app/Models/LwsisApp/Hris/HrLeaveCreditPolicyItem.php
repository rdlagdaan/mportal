<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveCreditPolicyItem extends Model
{
    protected $table = 'hris.hr_leave_credit_policy_items';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'policy_set_id',
        'leave_type_id',
        'enabled',
        'credit_amount',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'credit_amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function policySet()
    {
        return $this->belongsTo(HrLeaveCreditPolicySet::class, 'policy_set_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }
}