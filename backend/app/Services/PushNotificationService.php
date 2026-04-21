<?php

namespace App\Services;

use App\Models\LwsisApp\DeviceUserToken;
use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    public function send(int $userId, string $zone, string $event): void
    {
        $tokens = DeviceUserToken::where('user_id', $userId)
            ->pluck('device_token');

        foreach ($tokens as $token) {
            Http::post('https://exp.host/--/api/v2/push/send', [
                'to' => $token,
                'title' => 'Location Update',
                'body' => $event === 'entered'
                    ? "You arrived at {$zone}"
                    : "You left {$zone}",
                'data' => [
                    'type' => 'geofence',
                    'zone' => $zone,
                    'event'=> $event,
                ],
                'priority' => 'high',
                'sound' => 'default',
                
            ]);
        }
    }
}
