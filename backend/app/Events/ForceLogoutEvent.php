<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ForceLogoutEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId); // ✅ PUBLIC
    }

    public function broadcastAs(): string
    {
        return 'force.logout';
    }
}