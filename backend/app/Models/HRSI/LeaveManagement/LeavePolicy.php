<?php

namespace App\Models\HRSI\LeaveManagement;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    protected $fillable = [
        'school_year_id','leave_type_id','employment_class_id','annual_cap_days',
        'convertible_to_cash','overlap_allowed','requires_prior_notice_days','requires_med_cert'
    ];

    protected $casts = [
        'annual_cap_days'     => 'float',
        'convertible_to_cash' => 'bool',
        'overlap_allowed'     => 'bool',
    ];

    public function type()           { return $this->belongsTo(LeaveType::class,'leave_type_id'); }
    public function schoolYear()     { return $this->belongsTo(SchoolYear::class); }
    public function employmentClass(){ return $this->belongsTo(EmploymentClass::class); }
}
