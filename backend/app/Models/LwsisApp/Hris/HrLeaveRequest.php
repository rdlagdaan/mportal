<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveRequest extends Model
{
    protected $table = 'hris.hr_leave_requests';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'leave_type_id',
        'date_from',
        'date_to',
        'qty',
        'reason',
        'attachment_path',
        'is_without_pay',
        'status',
        'submitted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'qty' => 'decimal:2',
        'is_without_pay' => 'boolean',
        'submitted_at' => 'datetime',
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

    public function approvals()
    {
        return $this->hasMany(HrLeaveRequestApproval::class, 'leave_request_id');
    }

    public function routes()
    {
        return $this->hasMany(HrLeaveRequestRoute::class, 'leave_request_id')
                    ->orderBy('step_no');
    }

    public function obDetails()
    {
        return $this->hasOne(HrLeaveRequestObDetail::class, 'leave_request_id');
    }
}