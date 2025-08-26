<?php

namespace App\Notifications\HRSI\LeaveManagement;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class LeaveStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $leaveRequestId,
        public string $newStatus, // approved | rejected | posted
        public string $typeCode,
        public string $dateText,
        public int $employeeId
    ) {}

    public function via($notifiable) { return ['database','broadcast']; }

    public function toDatabase($notifiable)
    {
        return [
            'kind' => 'leave_status',
            'leave_request_id' => $this->leaveRequestId,
            'status' => $this->newStatus,
            'type_code' => $this->typeCode,
            'date_text' => $this->dateText,
            'employee_id' => $this->employeeId,
        ];
    }

    public function toBroadcast($notifiable) { return new BroadcastMessage($this->toDatabase($notifiable)); }

    public function broadcastOn(): array
    {
        return [ new PrivateChannel("employee.{$this->employeeId}") ];
    }

    public function broadcastType(): string { return 'leave.status'; }
}
