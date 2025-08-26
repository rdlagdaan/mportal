<?php

namespace App\Models\HRSI\LeaveManagement;

use App\Models\HRSI\Employee;
use Illuminate\Database\Eloquent\Model;

class LeaveApprovalStep extends Model
{
    protected $fillable = [
        'leave_request_id','step_order','approver_role','approver_employee_id',
        'status','acted_at','remarks'
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function request() { return $this->belongsTo(LeaveRequest::class,'leave_request_id'); }
    public function approver(){ return $this->belongsTo(Employee::class,'approver_employee_id'); }
}
