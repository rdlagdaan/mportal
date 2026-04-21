<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
   public static function sendNotification($deviceToken, $title, $body, $data = [])
{
    try {
        $serviceAccount = storage_path('app/tuamobileap-firebase-adminsdk-fbsvc-b005a71309.json');
        $projectId = 'tuamobileap';

        $client = new \Google\Client();
        $client->setAuthConfig($serviceAccount);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'sound' => 'default',
                        'color' => '#2D663E', // optional
                    ],
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $response = \Http::withToken($accessToken)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        \Log::info('📤 FCM Sent', [
            'token' => $deviceToken,
            'response' => $response->json(),
        ]);

        return $response->json();

    } catch (\Throwable $e) {
        \Log::error('🔥 FirebaseService sendNotification error', [
            'error' => $e->getMessage(),
            'token' => $deviceToken,
        ]);
        return ['error' => $e->getMessage()];
    }
}

}
