<?php

namespace App\Models\HRSI\LeaveManagement;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'code','name','counts_against_balance','requires_med_cert',
        'requires_prior_notice_days','chargeable_targets','default_daily_unit'
    ];

    protected $casts = [
        'counts_against_balance'   => 'bool',
        'requires_med_cert'        => 'bool',
        'chargeable_targets'       => 'array',
        'default_daily_unit'       => 'float',
    ];
}
