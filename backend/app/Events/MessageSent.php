<?php

    namespace App\Events;

    use App\Models\Mobile\AppointmentMessage;
    use Illuminate\Broadcasting\Channel;
    use Illuminate\Broadcasting\InteractsWithSockets;
    use Illuminate\Foundation\Events\Dispatchable;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

    class MessageSent implements ShouldBroadcast
    {
        use Dispatchable, InteractsWithSockets, SerializesModels;

        public $message;

        public function __construct(AppointmentMessage $message)
        {
            $this->message = $message->load('sender:id,name');
        }

        public function broadcastOn()
        {
            // The frontend listens to this channel
            return new Channel('chat.' . $this->message->appointment_id);
        }

        public function broadcastAs()
        {
            return 'MessageSent';
        }

        public function broadcastWith()
        {
            return [
                'message' => $this->message,
            ];
        }
    }