<?php

namespace App\Notifications\HRSI\LeaveManagement;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PendingApprovalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $approverEmployeeId, public int $pendingCount) {}

    public function via($notifiable) { return ['database']; }

    public function toDatabase($notifiable)
    {
        return [
            'kind' => 'approvals_reminder',
            'approver_employee_id' => $this->approverEmployeeId,
            'pending_count' => $this->pendingCount,
        ];
    }
}
