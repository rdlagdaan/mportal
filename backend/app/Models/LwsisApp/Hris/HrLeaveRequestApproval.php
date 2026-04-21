<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveRequestApproval extends Model
{
    protected $table = 'hris.hr_leave_request_approvals';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'leave_request_id',
        'level',
        'approver_employee_id',
        'acted_by_employee_id',
        'acted_by_user_id',
        'actor_role',
        'status',
        'acted_at',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'acted_at' => 'datetime',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(HrLeaveRequest::class, 'leave_request_id');
    }

    public function approver()
    {
        return $this->belongsTo(HrEmployee::class, 'approver_employee_id');
    }

    public function actedByEmployee()
    {
        return $this->belongsTo(HrEmployee::class, 'acted_by_employee_id');
    }

    public function actedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'acted_by_user_id');
    }
}