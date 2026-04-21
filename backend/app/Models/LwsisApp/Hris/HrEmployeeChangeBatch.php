<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeChangeBatch extends Model
{
    protected $table = 'hris.hr_employee_change_batches';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'requested_by',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'remarks',
        'created_at',
        'updated_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Employee na inaapplyan ng change
    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    // User na nag-request (employee din)
    public function requestedBy()
    {
        return $this->belongsTo(HrEmployee::class, 'requested_by');
    }

    // User na nag-review
    public function reviewedBy()
    {
        return $this->belongsTo(HrEmployee::class, 'reviewed_by');
    }

    // Details (expected pair table)
    public function details()
    {
        return $this->hasMany(HrEmployeeChangeDetail::class, 'batch_id');
    }
}