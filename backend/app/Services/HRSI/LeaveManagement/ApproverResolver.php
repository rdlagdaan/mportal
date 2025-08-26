<?php

namespace App\Services\HRSI\LeaveManagement;

use App\Models\HRSI\LeaveManagement\LeaveRequest;
use Illuminate\Support\Facades\DB;

class ApproverResolver
{
    public function buildSteps(LeaveRequest $lr): array
    {
        $empId = $lr->employee_id;

        $orgUnitId = DB::table('employee_role_assignments')
            ->where('employee_id', $empId)
            ->whereRaw('valid_during @> lower((SELECT period FROM leave_requests WHERE id = ?))', [$lr->id])
            ->orderByDesc('id')->value('org_unit_id');

        $org = DB::table('org_units')->where('id',$orgUnitId)->first();
        $reportsToPresident = (bool)($org->reports_to_president ?? false);

        $headId     = $this->findRoleHolder($orgUnitId, 'UNIT_HEAD', $lr);
        $deanId     = $this->findRoleHolder($orgUnitId, 'DEAN', $lr);
        $directorId = $this->findRoleHolder($orgUnitId, 'DIRECTOR', $lr);
        $vpId       = $this->findRoleHolder($orgUnitId, 'VP', $lr, true);
        $presId     = $this->findRoleHolder(null, 'PRESIDENT', $lr, true);

        $isVP        = $this->employeeHasRole($empId, 'VP', $lr);
        $isPresident = $this->employeeHasRole($empId, 'PRESIDENT', $lr);
        $isHeadAny   = $this->employeeHasAny($empId, ['UNIT_HEAD','DEAN','DIRECTOR'], $lr);

        $steps = [];

        if ($isPresident) {
            // none (or HR review if you want)
        } elseif ($isVP) {
            if ($presId) $steps[] = ['approver_role'=>'president','approver_employee_id'=>$presId];
        } elseif ($isHeadAny) {
            if ($reportsToPresident) {
                if ($presId) $steps[] = ['approver_role'=>'president','approver_employee_id'=>$presId];
            } else {
                if ($vpId) $steps[] = ['approver_role'=>'vp','approver_employee_id'=>$vpId];
            }
        } else {
            $first = $headId ?: $deanId ?: $directorId;
            if ($first) $steps[] = ['approver_role'=>'unit_head','approver_employee_id'=>$first];
            if ($reportsToPresident) {
                if ($presId) $steps[] = ['approver_role'=>'president','approver_employee_id'=>$presId];
            } else {
                if ($vpId) $steps[] = ['approver_role'=>'vp','approver_employee_id'=>$vpId];
            }
        }

        return $steps;
    }

    protected function employeeHasRole(int $employeeId, string $roleCode, LeaveRequest $lr): bool
    {
        return DB::table('employee_role_assignments AS era')
            ->join('employee_roles AS r','r.id','=','era.role_id')
            ->where('era.employee_id',$employeeId)
            ->where('r.code',$roleCode)
            ->whereRaw('era.valid_during @> lower((SELECT period FROM leave_requests WHERE id = ?))', [$lr->id])
            ->exists();
    }

    protected function employeeHasAny(int $employeeId, array $codes, LeaveRequest $lr): bool
    {
        return DB::table('employee_role_assignments AS era')
            ->join('employee_roles AS r','r.id','=','era.role_id')
            ->where('era.employee_id',$employeeId)
            ->whereIn('r.code',$codes)
            ->whereRaw('era.valid_during @> lower((SELECT period FROM leave_requests WHERE id = ?))', [$lr->id])
            ->exists();
    }

    protected function findRoleHolder(?int $orgUnitId, string $code, LeaveRequest $lr, bool $upChain=false): ?int
    {
        $q = DB::table('employee_role_assignments AS era')
            ->join('employee_roles AS r','r.id','=','era.role_id')
            ->where('r.code',$code)
            ->whereRaw('era.valid_during @> lower((SELECT period FROM leave_requests WHERE id = ?))', [$lr->id]);

        if ($orgUnitId) $q->where('era.org_unit_id',$orgUnitId);
        $empId = $q->value('employee_id');
        if ($empId || !$upChain) return $empId ?: null;

        $parentId = DB::table('org_units')->where('id',$orgUnitId)->value('parent_id');
        while ($parentId) {
            $empId = DB::table('employee_role_assignments AS era')
                ->join('employee_roles AS r','r.id','=','era.role_id')
                ->where('r.code',$code)
                ->where('era.org_unit_id',$parentId)
                ->whereRaw('era.valid_during @> lower((SELECT period FROM leave_requests WHERE id = ?))', [$lr->id])
                ->value('employee_id');
            if ($empId) return $empId;
            $parentId = DB::table('org_units')->where('id',$parentId)->value('parent_id');
        }

        if ($code === 'PRESIDENT') {
            return DB::table('employee_role_assignments AS era')
                ->join('employee_roles AS r','r.id','=','era.role_id')
                ->where('r.code','PRESIDENT')
                ->whereRaw('era.valid_during @> lower((SELECT period FROM leave_requests WHERE id = ?))', [$lr->id])
                ->value('employee_id');
        }
        return null;
    }
}
