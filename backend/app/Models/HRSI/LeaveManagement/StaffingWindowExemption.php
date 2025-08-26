<?php

namespace App\Models\HRSI\LeaveManagement;

use App\Models\HRSI\Employee;
use Illuminate\Database\Eloquent\Model;

class StaffingWindowExemption extends Model
{
    public $timestamps = false;

    protected $fillable = ['staffing_window_id','employee_id','leave_type_id','reason'];

    public function window()  { return $this->belongsTo(StaffingWindow::class, 'staffing_window_id'); }
    public function employee(){ return $this->belongsTo(Employee::class); }
    public function leaveType(){ return $this->belongsTo(LeaveType::class, 'leave_type_id'); }
}
