<?php

namespace App\Models\HRSI\LeaveManagement;

use App\Models\HRSI\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmployeeRoleAssignment extends Model
{
    protected $fillable = ['employee_id','role_id','org_unit_id'];

    public function employee(){ return $this->belongsTo(Employee::class); }
    public function role()    { return $this->belongsTo(EmployeeRole::class,'role_id'); }
    public function orgUnit() { return $this->belongsTo(OrgUnit::class); }

    /** Set the valid_during range as [start, end) (end nullable). */
    public function setValidDuring($start, $end = null): void
    {
        DB::statement(
            "UPDATE employee_role_assignments SET valid_during = daterange(?, ?, '[]') WHERE id = ?",
            [$start, $end, $this->id]
        );
    }
}
