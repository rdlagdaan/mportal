<?php

namespace App\Models\HRSI\LeaveManagement;

use App\Models\SchoolYear;
use App\Models\HRSI\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StaffingWindow extends Model
{
    protected $fillable = ['school_year_id','org_unit_id','name','created_by','notes'];

    public function schoolYear(){ return $this->belongsTo(SchoolYear::class); }
    public function orgUnit()   { return $this->belongsTo(OrgUnit::class); }
    public function creator()   { return $this->belongsTo(Employee::class, 'created_by'); }
    public function rules()     { return $this->hasMany(StaffingWindowRule::class); }
    public function exemptions(){ return $this->hasMany(StaffingWindowExemption::class); }

    public function setPeriod($startDate, $endDate): void
    {
        DB::statement(
            "UPDATE staffing_windows SET period = daterange(?, ?, '[]') WHERE id = ?",
            [$startDate, $endDate, $this->id]
        );
    }
}
