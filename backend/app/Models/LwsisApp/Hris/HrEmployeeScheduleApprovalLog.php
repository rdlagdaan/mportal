<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeScheduleApprovalLog extends Model
{
    protected $table = 'hris.hr_employee_schedule_approval_logs';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'batch_id',
        'action',
        'acted_by_user_id',
        'acted_by_employee_id',
        'remarks',
        'acted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Schedule batch
    public function batch()
    {
        return $this->belongsTo(HrEmployeeScheduleBatch::class, 'batch_id');
    }

    // User (auth system)
    public function actedByUser()
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    // Employee (HRIS)
    public function actedByEmployee()
    {
        return $this->belongsTo(HrEmployee::class, 'acted_by_employee_id');
    }
}