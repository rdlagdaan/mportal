<?php

namespace App\Models\HRSI\LeaveManagement;

use Illuminate\Database\Eloquent\Model;

class LeaveBalanceLedger extends Model
{
    protected $table = 'leave_balance_ledger';

    protected $fillable = [
        'employee_id','school_year_id','leave_type_id','entry_date',
        'qty_days','reason','reference_id','meta'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'qty_days'   => 'float',
        'meta'       => 'array',
    ];
}
