<?php

namespace App\Services\HRSI\LeaveManagement;

use App\Models\User;
use App\Models\HRSI\LeaveManagement\LeaveRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Notifier
{
    public function notifyEmployeeId(int $employeeId, Notification $notification): void
    {
        $userId = DB::table('employees')->where('id', $employeeId)->value('user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) $user->notify($notification);
        }
    }

    public function typeCode(int $leaveTypeId): string
    {
        return (string) DB::table('leave_types')->where('id',$leaveTypeId)->value('code');
    }

    public function employeeName(int $employeeId): string
    {
        return (string) DB::table('employees')
            ->selectRaw("TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) as n")
            ->where('id',$employeeId)->value('n') ?? ("Employee #".$employeeId);
    }

    public function periodTextFromRequest(int $leaveRequestId): string
    {
        $row = DB::selectOne("SELECT lower(period) s, upper(period) e FROM leave_requests WHERE id=?", [$leaveRequestId]);
        if (!$row) return '';
        $s = Carbon::parse($row->s)->toDateString();
        $e = Carbon::parse($row->e)->toDateString();
        return "$s to $e";
    }

    public function notifyFirstApproverOnSubmit(LeaveRequest $lr): void
    {
        $first = $lr->steps->sortBy('step_order')->first();
        if (!$first || !$first->approver_employee_id) return;

        $this->notifyEmployeeId(
            $first->approver_employee_id,
            new \App\Notifications\HRSI\LeaveManagement\LeaveSubmitted(
                leaveRequestId: $lr->id,
                employeeId: $lr->employee_id,
                employeeName: $this->employeeName($lr->employee_id),
                typeCode: $this->typeCode($lr->leave_type_id),
                dateText: $this->periodTextFromRequest($lr->id),
                approverEmployeeId: $first->approver_employee_id
            )
        );
    }

    public function notifyAfterApproval(LeaveRequest $lr): void
    {
        $pending = $lr->steps()->where('status','pending')->orderBy('step_order')->first();
        $typeCode = $this->typeCode($lr->leave_type_id);
        $dateText = $this->periodTextFromRequest($lr->id);

        if ($pending && $pending->approver_employee_id) {
            $this->notifyEmployeeId(
                $pending->approver_employee_id,
                new \App\Notifications\HRSI\LeaveManagement\LeaveSubmitted(
                    leaveRequestId: $lr->id,
                    employeeId: $lr->employee_id,
                    employeeName: $this->employeeName($lr->employee_id),
                    typeCode: $typeCode,
                    dateText: $dateText,
                    approverEmployeeId: $pending->approver_employee_id
                )
            );
        } else {
            $this->notifyEmployeeId(
                $lr->employee_id,
                new \App\Notifications\HRSI\LeaveManagement\LeaveStatusChanged(
                    $lr->id, 'approved', $typeCode, $dateText, $lr->employee_id
                )
            );
        }
    }

    public function notifyRejected(LeaveRequest $lr): void
    {
        $this->notifyEmployeeId(
            $lr->employee_id,
            new \App\Notifications\HRSI\LeaveManagement\LeaveStatusChanged(
                $lr->id, 'rejected', $this->typeCode($lr->leave_type_id), $this->periodTextFromRequest($lr->id), $lr->employee_id
            )
        );
    }

    public function notifyPosted(LeaveRequest $lr): void
    {
        $this->notifyEmployeeId(
            $lr->employee_id,
            new \App\Notifications\HRSI\LeaveManagement\LeaveStatusChanged(
                $lr->id, 'posted', $this->typeCode($lr->leave_type_id), $this->periodTextFromRequest($lr->id), $lr->employee_id
            )
        );
    }

    public function notifyBalanceThreshold(int $employeeId, int $schoolYearId, string $typeCode, float $balance, float $requested): void
    {
        $this->notifyEmployeeId(
            $employeeId,
            new \App\Notifications\HRSI\LeaveManagement\LeaveBalanceThreshold(
                $employeeId, $schoolYearId, $typeCode, $balance, $requested
            )
        );
    }
}
