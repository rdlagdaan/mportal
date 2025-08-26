<?php

namespace App\Services\HRSI\LeaveManagement;

use App\Models\HRSI\LeaveManagement\LeavePolicy;
use Illuminate\Support\Facades\DB;

class LeavePolicyService
{
    /**
     * Resolve the effective policy row for (school_year_id, leave_type_id, employee_id).
     * Assumes employees.employment_class_id exists (NULL allowed -> match policy with NULL).
     */
    public function resolvePolicy(int $schoolYearId, int $leaveTypeId, int $employeeId): ?LeavePolicy
    {
        $emp = DB::table('employees')->select('employment_class_id')->where('id',$employeeId)->first();
        $classId = $emp?->employment_class_id;

        $q = LeavePolicy::query()
            ->where('school_year_id', $schoolYearId)
            ->where('leave_type_id', $leaveTypeId)
            ->orderByRaw('CASE WHEN employment_class_id IS NULL THEN 1 ELSE 0 END'); // prefer specific class

        if ($classId) {
            $q->whereIn('employment_class_id', [$classId, null]);
        } else {
            $q->whereNull('employment_class_id');
        }

        return $q->first();
    }
}
