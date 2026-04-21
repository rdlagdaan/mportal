<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;

    public function __construct(string $message = 'Hello from Reverb!')
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // You can change this to "PrivateChannel" if you’re using auth
        return new Channel('notifications');
    }

    public function broadcastAs()
    {
        return 'test.notification';
    }
}
