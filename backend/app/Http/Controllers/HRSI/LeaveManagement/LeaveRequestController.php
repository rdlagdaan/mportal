<?php

namespace App\Http\Controllers\HRSI\LeaveManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRSI\LeaveManagement\StoreLeaveRequestRequest;
use App\Http\Requests\HRSI\LeaveManagement\UpdateLeaveRequestRequest;
use App\Models\HRSI\LeaveManagement\LeaveRequest;
use App\Models\HRSI\LeaveManagement\LeaveApprovalStep;
use App\Services\HRSI\LeaveManagement\ApproverResolver;
use App\Services\HRSI\LeaveManagement\LeaveValidationService;
use App\Services\HRSI\LeaveManagement\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    public function index(Request $req)
    {
        $q = LeaveRequest::query()->with('steps');
        if ($req->filled('employee_id')) $q->where('employee_id', $req->integer('employee_id'));
        if ($req->filled('status'))      $q->where('status', $req->input('status'));
        if ($req->filled('type'))        $q->where('leave_type_id', $req->integer('type'));
        return $q->orderByDesc('id')->paginate(20);
    }

    public function show($id) { return LeaveRequest::with(['steps','docs'])->findOrFail($id); }

    public function store(StoreLeaveRequestRequest $request)
    {
        $data = $request->validated();

        $lr = DB::transaction(function () use ($data) {
            $lr = LeaveRequest::create([
                'employee_id'             => $data['employee_id'],
                'school_year_id'          => $data['school_year_id'],
                'leave_type_id'           => $data['leave_type_id'],
                'charge_to_leave_type_id' => $data['charge_to_leave_type_id'] ?? null,
                'part_day'                => $data['part_day'] ?? null,
                'reason_text'             => $data['reason_text'] ?? null,
                'status'                  => 'draft',
            ]);
            $lr->setPeriod($data['start_date'], $data['end_date']);
            return $lr;
        });

        return response()->json($lr->fresh(), 201);
    }

    public function update($id, UpdateLeaveRequestRequest $request)
    {
        $data = $request->validated();
        $lr = LeaveRequest::findOrFail($id);

        if (! in_array($lr->status, ['draft','submitted'])) {
            return response()->json(['error'=>'Only draft/submitted leaves can be updated.'], 422);
        }

        DB::transaction(function () use ($lr, $data) {
            $lr->fill([
                'leave_type_id'           => $data['leave_type_id'] ?? $lr->leave_type_id,
                'charge_to_leave_type_id' => $data['charge_to_leave_type_id'] ?? $lr->charge_to_leave_type_id,
                'part_day'                => $data['part_day'] ?? $lr->part_day,
                'reason_text'             => $data['reason_text'] ?? $lr->reason_text,
            ])->save();

            if (isset($data['start_date']) || isset($data['end_date'])) {
                $start = $data['start_date'] ?? DB::table('leave_requests')->selectRaw('lower(period) s')->where('id',$lr->id)->value('s');
                $end   = $data['end_date']   ?? DB::table('leave_requests')->selectRaw('upper(period) e')->where('id',$lr->id)->value('e');
                $lr->setPeriod($start, $end);
            }
        });

        return $lr->fresh();
    }

    public function destroy($id)
    {
        $lr = LeaveRequest::findOrFail($id);
        if ($lr->status !== 'draft') return response()->json(['error'=>'Only draft leaves can be deleted.'], 422);
        $lr->delete(); return response()->noContent();
    }

    public function submit($id, Request $req, ApproverResolver $resolver, LeaveValidationService $validator, Notifier $notifier)
    {
        $lr = LeaveRequest::findOrFail($id);
        $validator->validateBeforeSubmit($lr->id);

        $lr = DB::transaction(function () use ($lr, $resolver) {
            $lr->status = 'submitted';
            $lr->submitted_at = now();
            $lr->save();

            $steps = $resolver->buildSteps($lr);
            LeaveApprovalStep::where('leave_request_id',$lr->id)->delete();
            foreach ($steps as $i => $s) {
                LeaveApprovalStep::create([
                    'leave_request_id'     => $lr->id,
                    'step_order'           => $i + 1,
                    'approver_role'        => $s['approver_role'],
                    'approver_employee_id' => $s['approver_employee_id'] ?? null,
                    'status'               => 'pending',
                ]);
            }
            return $lr->load('steps');
        });

        $notifier->notifyFirstApproverOnSubmit($lr);
        return $lr;
    }

    public function cancel($id)
    {
        $lr = LeaveRequest::findOrFail($id);
        if (! in_array($lr->status, ['draft','submitted','under_review'])) {
            return response()->json(['error'=>'Only draft/submitted/under_review can be cancelled.'], 422);
        }
        $lr->status = 'cancelled'; $lr->save(); return $lr;
    }
}
