<?php

namespace App\Notifications\HRSI\LeaveManagement;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class LeaveBalanceThreshold extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $employeeId,
        public int $schoolYearId,
        public string $typeCode,
        public float $balanceDays,
        public float $requestedDays
    ) {}

    public function via($notifiable) { return ['database','broadcast']; }

    public function toDatabase($notifiable)
    {
        return [
            'kind' => 'leave_balance_threshold',
            'employee_id' => $this->employeeId,
            'school_year_id' => $this->schoolYearId,
            'type_code' => $this->typeCode,
            'balance_days' => $this->balanceDays,
            'requested_days' => $this->requestedDays,
        ];
    }

    public function toBroadcast($notifiable) { return new BroadcastMessage($this->toDatabase($notifiable)); }

    public function broadcastOn(): array
    {
        return [ new PrivateChannel("employee.{$this->employeeId}") ];
    }

    public function broadcastType(): string { return 'leave.balance'; }
}
