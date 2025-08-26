<?php

namespace App\Notifications\HRSI\LeaveManagement;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class LeaveSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $leaveRequestId,
        public int $employeeId,
        public string $employeeName,
        public string $typeCode,
        public string $dateText,
        public int $approverEmployeeId
    ) {}

    public function via($notifiable) { return ['database','broadcast']; }

    public function toDatabase($notifiable)
    {
        return [
            'kind' => 'leave_submitted',
            'leave_request_id' => $this->leaveRequestId,
            'employee_id' => $this->employeeId,
            'employee_name' => $this->employeeName,
            'type_code' => $this->typeCode,
            'date_text' => $this->dateText,
            'approver_employee_id' => $this->approverEmployeeId,
        ];
    }

    public function toBroadcast($notifiable) { return new BroadcastMessage($this->toDatabase($notifiable)); }

    public function broadcastOn(): array
    {
        return [ new PrivateChannel("approver.{$this->approverEmployeeId}") ];
    }

    public function broadcastType(): string { return 'leave.submitted'; }
}
