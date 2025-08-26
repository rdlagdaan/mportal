<?php

namespace App\Http\Controllers\HRSI\LeaveManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRSI\LeaveManagement\ApproveStepRequest;
use App\Http\Requests\HRSI\LeaveManagement\RejectStepRequest;
use App\Models\HRSI\LeaveManagement\LeaveApprovalStep;
use App\Services\HRSI\LeaveManagement\LeaveValidationService;
use App\Services\HRSI\LeaveManagement\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveApprovalController extends Controller
{
    public function inbox(Request $req)
    {
        $employeeId = (int) optional($req->user()->employee)->id;
        $q = LeaveApprovalStep::query()->with('request')->where('status','pending');
        if ($employeeId) $q->where('approver_employee_id', $employeeId);
        return $q->orderBy('step_order')->paginate(20);
    }

    public function approve($stepId, ApproveStepRequest $request, LeaveValidationService $validator, Notifier $notifier)
    {
        $step = LeaveApprovalStep::with('request')->findOrFail($stepId);

        $lr = DB::transaction(function () use ($step, $request, $validator) {
            $step->status = 'approved'; $step->acted_at = now(); $step->remarks = $request->input('remarks'); $step->save();
            $lr = $step->request()->lockForUpdate()->first();
            $validator->validateAtApproval($lr->id);
            $next = $lr->steps()->where('status','pending')->orderBy('step_order')->first();
            if ($next) { $lr->status = 'under_review'; }
            else       { $lr->status = 'approved'; $lr->final_decision_at = now(); }
            $lr->save();
            return $lr->load('steps');
        });

        $notifier->notifyAfterApproval($lr);
        return $lr;
    }

    public function reject($stepId, RejectStepRequest $request, Notifier $notifier)
    {
        $step = LeaveApprovalStep::with('request')->findOrFail($stepId);

        $lr = DB::transaction(function () use ($step, $request) {
            $step->status = 'rejected'; $step->acted_at = now(); $step->remarks = $request->input('remarks'); $step->save();
            $lr = $step->request()->lockForUpdate()->first();
            $lr->status = 'rejected'; $lr->final_decision_at = now(); $lr->save();
            return $lr->load('steps');
        });

        $notifier->notifyRejected($lr);
        return $lr;
    }
}
