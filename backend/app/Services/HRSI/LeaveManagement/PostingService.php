<?php

namespace App\Services\HRSI\LeaveManagement;

use App\Models\HRSI\LeaveManagement\LeaveBalanceLedger;
use App\Models\HRSI\LeaveManagement\LeaveBooking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostingService
{
    public function __construct(protected CalendarService $calendar) {}

    /**
     * Post an APPROVED request: check balance, write ledger debit, create booking, set status=posted.
     * Returns refreshed leave_request row.
     */
    public function post(int $leaveRequestId, ?int $chargeToLeaveTypeId = null)
    {
        return DB::transaction(function () use ($leaveRequestId, $chargeToLeaveTypeId) {

            $lr = DB::table('leave_requests')->where('id',$leaveRequestId)->lockForUpdate()->first();
            if (!$lr) throw ValidationException::withMessages(['request' => 'Leave request not found.']);
            if ($lr->status !== 'approved') {
                throw ValidationException::withMessages(['status' => 'Only approved requests can be posted.']);
            }

            $effectiveTypeId = $chargeToLeaveTypeId ?: ($lr->charge_to_leave_type_id ?: $lr->leave_type_id);

            // Compute units to debit (subtract holidays/weekends, consider part_day)
            $units = $this->calendar->leaveUnitsForRequest($lr->id, $lr->school_year_id, $lr->part_day);

            // Check remaining balance (counts only if type counts against balance)
            $counts = (bool) DB::table('leave_types')->where('id',$effectiveTypeId)->value('counts_against_balance');
            if ($counts) {
                $bal = (float) DB::table('leave_balances')
                    ->where('employee_id',$lr->employee_id)
                    ->where('school_year_id',$lr->school_year_id)
                    ->where('leave_type_id',$effectiveTypeId)
                    ->value('balance_days') ?? 0.0;

                if ($bal - $units < -1e-9) {
                    throw ValidationException::withMessages(['balance' => "Insufficient balance: need {$units}, available {$bal}."]);
                }
            }

            // Ledger debit (negative)
            if ($counts && $units > 0) {
                LeaveBalanceLedger::create([
                    'employee_id'    => $lr->employee_id,
                    'school_year_id' => $lr->school_year_id,
                    'leave_type_id'  => $effectiveTypeId,
                    'qty_days'       => -$units,
                    'reason'         => 'approval_post',
                    'reference_id'   => $lr->id,
                    'meta'           => ['original_leave_type_id' => $lr->leave_type_id],
                ]);
            }

            // Booking (will fail on overlap due to EXCLUDE constraint)
            $bk = LeaveBooking::create([
                'employee_id'   => $lr->employee_id,
                'leave_type_id' => $effectiveTypeId,
            ]);
            // Copy period straight from request
            DB::statement("
                UPDATE leave_bookings SET period = (SELECT period FROM leave_requests WHERE id = ?) WHERE id = ?
            ", [$lr->id, $bk->id]);

            // Mark posted
            DB::table('leave_requests')->where('id',$lr->id)->update(['status'=>'posted']);

            return DB::table('leave_requests')->where('id',$lr->id)->first();
        });
    }
}
