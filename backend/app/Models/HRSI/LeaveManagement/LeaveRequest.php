<?php

namespace App\Models\HRSI\LeaveManagement;

use App\Models\HRSI\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id','school_year_id','leave_type_id','charge_to_leave_type_id',
        'part_day','reason_text','status','requires_med_cert','prior_notice_days',
        'submitted_at','final_decision_at'
    ];

    protected $casts = [
        'submitted_at'     => 'datetime',
        'final_decision_at'=> 'datetime',
    ];

    public function employee(){ return $this->belongsTo(Employee::class); }
    public function type()    { return $this->belongsTo(LeaveType::class,'leave_type_id'); }
    public function chargeTo(){ return $this->belongsTo(LeaveType::class,'charge_to_leave_type_id'); }
    public function steps()   { return $this->hasMany(LeaveApprovalStep::class); }
    public function docs()    { return $this->hasMany(LeaveRequestDoc::class); }

    /** Set the leave period as [start, end). */
    public function setPeriod($startDate, $endDate): void
    {
        DB::statement(
            "UPDATE leave_requests SET period = daterange(?, ?, '[]') WHERE id = ?",
            [$startDate, $endDate, $this->id]
        );
    }
}
