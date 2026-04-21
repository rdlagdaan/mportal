<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveLedger extends Model
{
    protected $table = 'hris.hr_leave_ledger';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'leave_type_id',
        'leave_year',
        'txn_date',
        'txn_type',
        'entry_type',
        'qty',
        'reference_type',
        'reference_id',
        'remarks',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'leave_year' => 'integer',
        'qty' => 'decimal:2',
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

    public function leaveType()
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}