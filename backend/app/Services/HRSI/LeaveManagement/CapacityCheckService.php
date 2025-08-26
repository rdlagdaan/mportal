<?php

namespace App\Services\HRSI\LeaveManagement;

use Illuminate\Support\Facades\DB;

class CapacityCheckService
{
    /**
     * Evaluate all staffing windows overlapping the request period for the employee's org unit,
     * and return blocking info if any cap would be exceeded.
     *
     * @return array{allowed:bool, blocks:array[]}
     */
    public function check(int $leaveRequestId): array
    {
        $lr = DB::table('leave_requests')
            ->select('id','employee_id','school_year_id','leave_type_id','status',
                     DB::raw('lower(period) AS start_date'),
                     DB::raw('upper(period) AS end_date'))
            ->where('id',$leaveRequestId)->first();

        if (!$lr) return ['allowed'=>true,'blocks'=>[]];

        // Resolve employee's org_unit during the start date (using role assignments)
        $orgUnitId = DB::table('employee_role_assignments')
            ->where('employee_id', $lr->employee_id)
            ->whereRaw('valid_during @> ?', [$lr->start_date])
            ->orderByDesc('id')->value('org_unit_id');

        if (!$orgUnitId) return ['allowed'=>true,'blocks'=>[]]; // no org -> nothing to check

        // Find windows in same SY for that org that overlap
        $windows = DB::table('staffing_windows as w')
            ->join('staffing_window_rules as r','r.staffing_window_id','=','w.id')
            ->select('w.id as window_id','w.name','r.id as rule_id','r.role_id','r.employment_class_id','r.leave_type_id',
                     'r.max_on_leave_count','r.max_on_leave_percent',
                     DB::raw('lower(w.period) AS w_start'), DB::raw('upper(w.period) AS w_end'))
            ->where('w.school_year_id', $lr->school_year_id)
            ->where('w.org_unit_id', $orgUnitId)
            ->whereRaw("w.period && daterange(?, ?, '[]')", [$lr->start_date, $lr->end_date])
            ->get();

        $blocks = [];

        foreach ($windows as $win) {
            // Exemptions?
            $isExempt = DB::table('staffing_window_exemptions')
                ->where('staffing_window_id',$win->window_id)
                ->where('employee_id',$lr->employee_id)
                ->when($win->leave_type_id, fn($q)=>$q->where('leave_type_id',$win->leave_type_id))
                ->exists();
            if ($isExempt) continue;

            // If rule targets a specific leave type and this request is different, skip
            if ($win->leave_type_id && (int)$win->leave_type_id !== (int)$lr->leave_type_id) continue;

            // Count overlapping leave requests in the same window and org, excluding rejected/cancelled
            $onLeaveCount = $this->countOverlappingLeaves($win, $orgUnitId, $lr->start_date, $lr->end_date);

            // Determine group size if percent rule is used (staff size for targeted subset)
            $groupSize = null;
            if ($win->max_on_leave_percent !== null) {
                $groupSize = $this->countActiveStaffInOrg($orgUnitId, $lr->start_date, $win->role_id, $win->employment_class_id);
            }

            // Apply count cap
            if ($win->max_on_leave_count !== null && $onLeaveCount + 1 > (int)$win->max_on_leave_count) {
                $blocks[] = [
                    'window_id' => $win->window_id,
                    'rule_id'   => $win->rule_id,
                    'reason'    => "Capacity limit reached: max {$win->max_on_leave_count} on leave for '{$win->name}'.",
                    'current'   => $onLeaveCount,
                    'limit'     => (int)$win->max_on_leave_count,
                    'type'      => 'count'
                ];
                continue;
            }

            // Apply percent cap
            if ($groupSize && $groupSize > 0 && $win->max_on_leave_percent !== null) {
                $projected = ($onLeaveCount + 1) / $groupSize * 100;
                if ($projected - 1e-9 > (float)$win->max_on_leave_percent) {
                    $blocks[] = [
                        'window_id' => $win->window_id,
                        'rule_id'   => $win->rule_id,
                        'reason'    => sprintf("Capacity limit reached: %.0f%% cap for '%s' (%.0f/%.0f).",
                                               $win->max_on_leave_percent, $win->name,
                                               $onLeaveCount + 1, $groupSize),
                        'current'   => $onLeaveCount,
                        'limit'     => (float)$win->max_on_leave_percent,
                        'group'     => $groupSize,
                        'type'      => 'percent'
                    ];
                }
            }
        }

        return ['allowed' => count($blocks) === 0, 'blocks' => $blocks];
    }

    protected function countOverlappingLeaves($win, int $orgUnitId, string $start, string $end): int
    {
        // statuses to include (not rejected/cancelled)
        $statuses = ['submitted','under_review','approved','posted'];

        // Join leave_requests with role assignments of their owners during the period, filtered to same org
        $sql = "
          SELECT COUNT(DISTINCT lr.id) AS c
          FROM leave_requests lr
          JOIN employee_role_assignments era
            ON era.employee_id = lr.employee_id
           AND era.valid_during @> lower(lr.period)
          WHERE lr.status = ANY(?)
            AND lr.leave_type_id = COALESCE(?, lr.leave_type_id)
            AND era.org_unit_id = ?
            AND lr.period && daterange(?, ?, '[]')
        ";

        $bindings = [ '{'.implode(',', $statuses).'}', $win->leave_type_id, $orgUnitId, $start, $end ];
        $row = DB::selectOne($sql, $bindings);
        return (int)($row->c ?? 0);
    }

    protected function countActiveStaffInOrg(int $orgUnitId, string $asOfDate, ?int $roleId, ?int $classId): int
    {
        $q = DB::table('employee_role_assignments as era')
            ->join('employees as e','e.id','=','era.employee_id')
            ->where('era.org_unit_id', $orgUnitId)
            ->whereRaw('era.valid_during @> ?', [$asOfDate]);

        if ($roleId) $q->where('era.role_id',$roleId);
        if ($classId) $q->where('e.employment_class_id',$classId);

        return (int)$q->distinct('era.employee_id')->count('era.employee_id');
    }
}
