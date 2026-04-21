<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $leaveId;
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(int $leaveId, array $payload = [])
    {
        $this->leaveId = $leaveId;
        $this->payload = $payload;
    }

    /**
     * The broadcasting channel the event should broadcast on.
     * Matches the frontend subscription: echo.private(`leave.status.${employeeId}`)
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("leave.status.{$this->leaveId}");
    }

    /**
     * Event name received in Echo: ".LeaveStatusUpdated"
     */
    public function broadcastAs(): string
    {
        return 'LeaveStatusUpdated';
    }

    /**
     * Optional: control payload shape
     */
    public function broadcastWith(): array
    {
        return [
            'leaveId' => $this->leaveId,
            'data'    => $this->payload,
        ];
    }
}
