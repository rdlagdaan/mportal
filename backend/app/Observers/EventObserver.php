<?php

namespace App\Observers;

use App\Models\Mobile\Event;
use App\Models\LwsisApp\DeviceUserToken;
use App\Helpers\NotificationHelper;

class EventObserver
{
    public function updated(Event $event)
    {
        if ($event->wasChanged('event_status')) {

            $status = $event->event_status;
            $title  = "Event Update: {$event->event_name}";
            $body   = match ($status) {
                'ongoing' => "🚀 The event '{$event->event_name}' is now ongoing!",
                'done'    => "✅ The event '{$event->event_name}' has ended.",
                default   => "📅 The event '{$event->event_name}' is coming soon!",
            };

            // ✅ Force-cast collection to array explicitly
            $deviceTokens = DeviceUserToken::pluck('device_token')->all();

            if (is_string($deviceTokens)) {
                // Defensive fix if someone plucked incorrectly
                $deviceTokens = [$deviceTokens];
            }

            if (!empty($deviceTokens)) {
                NotificationHelper::sendPushAndSave(
    $deviceTokens,
    $title,
    $body,
    'event',
    [
        'type'            => 'event',
        'event_id'        => $event->event_id,
        'notification_id' => $event->event_id,
        'status'          => $event->event_status,
    ]
);

            }
        }
    }
}