<?php

namespace App\Models\HRSI\LeaveManagement;

use Illuminate\Database\Eloquent\Model;

class StaffingWindowRule extends Model
{
    protected $fillable = [
        'staffing_window_id','role_id','employment_class_id','leave_type_id',
        'max_on_leave_count','max_on_leave_percent'
    ];

    public function window()         { return $this->belongsTo(StaffingWindow::class, 'staffing_window_id'); }
    public function role()           { return $this->belongsTo(EmployeeRole::class, 'role_id'); }
    public function employmentClass(){ return $this->belongsTo(EmploymentClass::class, 'employment_class_id'); }
    public function leaveType()      { return $this->belongsTo(LeaveType::class, 'leave_type_id'); }
}
