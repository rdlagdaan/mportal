<?php

namespace App\Http\Controllers\HRSI\LeaveManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRSI\LeaveManagement\PostLeaveRequest;
use App\Services\HRSI\LeaveManagement\PostingService;
use App\Services\HRSI\LeaveManagement\Notifier;
use Illuminate\Support\Facades\DB;

class LeavePostingController extends Controller
{
    public function queue()
    {
        return DB::table('leave_requests')
            ->where('status','approved')
            ->orderBy('final_decision_at','desc')
            ->paginate(20);
    }

    public function post($requestId, PostLeaveRequest $request, PostingService $posting, Notifier $notifier)
    {
        $lrRow = $posting->post((int)$requestId, $request->integer('charge_to_leave_type_id') ?: null);
        $notifier->notifyPosted(\App\Models\HRSI\LeaveManagement\LeaveRequest::findOrFail($lrRow->id));
        return $lrRow;
    }
}
