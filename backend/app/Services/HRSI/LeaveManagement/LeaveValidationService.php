<?php

namespace App\Services\HRSI\LeaveManagement;

use App\Models\HRSI\LeaveManagement\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveValidationService
{
    public function __construct(
        protected CalendarService $calendar,
        protected LeavePolicyService $policies,
        protected CapacityCheckService $capacity
    ) {}

    /**
     * Validate before SUBMITTING a draft leave.
     * Throws ValidationException on failure.
     */
    public function validateBeforeSubmit(int $leaveRequestId): void
    {
        $lr = DB::table('leave_requests')
            ->select('id','employee_id','school_year_id','leave_type_id','charge_to_leave_type_id','part_day',
                     'status', DB::raw('lower(period) AS start_date'), DB::raw('upper(period) AS end_date'))
            ->where('id',$leaveRequestId)->first();

        if (!$lr) throw ValidationException::withMessages(['request' => 'Leave request not found.']);
        if ($lr->status !== 'draft') throw ValidationException::withMessages(['status' => 'Only draft leaves can be submitted.']);

        $type = LeaveType::findOrFail($lr->leave_type_id);
        $policy = $this->policies->resolvePolicy($lr->school_year_id, $lr->leave_type_id, $lr->employee_id);

        // 1) Prior notice (VL or policy override)
        if ($policy && ($policy->requires_prior_notice_days ?? $type->requires_prior_notice_days) > 0) {
            $required = (int)($policy->requires_prior_notice_days ?? $type->requires_prior_notice_days);
            $today = Carbon::now();
            $start = Carbon::parse($lr->start_date);
            $wd = $this->calendar->workingDays($today->copy()->startOfDay(), $start->copy()->startOfDay(), $lr->school_year_id);
            if ($wd < $required) {
                throw ValidationException::withMessages([
                    'prior_notice' => "Prior notice of {$required} working days required; only {$wd} available."
                ]);
            }
        }

        // 2) Birthday leave (BL) must be within birth month and max 1 working day
        if ($type->code === 'BL') {
            $emp = DB::table('employees')->select('date_of_birth','birth_date')->where('id',$lr->employee_id)->first();
            $dob = $emp?->birth_date ?? $emp?->date_of_birth; // support either column name
            if (!$dob) throw ValidationException::withMessages(['birthday' => 'Employee birth date not set.']);
            $birthMonth = Carbon::parse($dob)->month;
            $startMonth = Carbon::parse($lr->start_date)->month;
            if ($birthMonth !== $startMonth) {
                throw ValidationException::withMessages(['birthday' => 'Birthday leave must be taken within your birth month.']);
            }
            $units = $this->calendar->leaveUnitsForRequest($lr->id, $lr->school_year_id, $lr->part_day);
            if ($units > 1.0) {
                throw ValidationException::withMessages(['birthday' => 'Birthday leave is limited to one (1) working day.']);
            }
        }

        // 3) Sick leave requires medical certificate (policy may override)
        $requiresMed = ($policy && $policy->requires_med_cert !== null)
            ? (bool)$policy->requires_med_cert
            : (bool)$type->requires_med_cert;

        if ($requiresMed && !$this->hasDocument($lr->id, 'med_cert')) {
            throw ValidationException::withMessages(['med_cert' => 'Medical certificate is required for this Sick Leave.']);
        }

        // 4) Emergency leave charge target optional here (can be set at posting),
        // but if provided, it must be allowed via chargeable_targets
        if ($type->code === 'EL' && $lr->charge_to_leave_type_id) {
            $targetCode = DB::table('leave_types')->where('id',$lr->charge_to_leave_type_id)->value('code');
            $allowed = collect($type->chargeable_targets ?? [])->contains($targetCode);
            if (!$allowed) {
                throw ValidationException::withMessages(['charge_to' => "EL cannot be charged to {$targetCode}."]);
            }
        }

        // 5) Holidays-only protection: warn/deny zero-value requests (all days non-working)
        $units = $this->calendar->leaveUnitsForRequest($lr->id, $lr->school_year_id, $lr->part_day);
        if ($units <= 0) {
            throw ValidationException::withMessages(['period' => 'Selected period contains no working days.']);
        }

        // 6) Critical staffing capacity (depending on config)
        if (Config::get('leave.capacity_enforcement_stage') === 'submit') {
            $cap = $this->capacity->check($lr->id);
            if (!$cap['allowed']) {
                $msg = collect($cap['blocks'])->pluck('reason')->implode(' ');
                throw ValidationException::withMessages(['capacity' => $msg]);
            }
        }
    }

    /**
     * Validate just-in-time at APPROVAL (in case many requests arrived since submit).
     */
    public function validateAtApproval(int $leaveRequestId): void
    {
        if (Config::get('leave.capacity_enforcement_stage') !== 'approval') return;
        $cap = $this->capacity->check($leaveRequestId);
        if (!$cap['allowed']) {
            $msg = collect($cap['blocks'])->pluck('reason')->implode(' ');
            throw ValidationException::withMessages(['capacity' => $msg]);
        }
    }

    protected function hasDocument(int $leaveRequestId, string $docType): bool
    {
        return DB::table('leave_request_docs')->where('leave_request_id',$leaveRequestId)->where('doc_type',$docType)->exists();
    }
}
