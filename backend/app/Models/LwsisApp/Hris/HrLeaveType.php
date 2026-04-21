<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveType extends Model
{
    protected $table = 'hris.hr_leave_types';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'code',
        'name',
        'unit',
        'requires_attachment',
        'is_paid',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'requires_attachment' => 'boolean',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function policies()
    {
        return $this->hasMany(HrLeavePolicy::class, 'leave_type_id');
    }

    public function ledger()
    {
        return $this->hasMany(HrLeaveLedger::class, 'leave_type_id');
    }

    public function consumptions()
    {
        return $this->hasMany(HrLeaveTypeConsumption::class, 'leave_type_id');
    }
}