<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Mobile\Notification;
use App\Models\Mobile\IamUserNotification;
use App\Models\LwsisApp\Users; 

class NotificationHelper
{
    public static function sendPushAndSave(array $deviceTokens, string $title, string $body, string $type = 'event', array $extraData = []): void
    {
        // ✅ 1. Send push notification to eventExpo
        foreach ($deviceTokens as $token) {
            if (empty($token)) continue;

            try {
                Http::post('https://exp.host/--/api/v2/push/send', [
                    'to' => $token,
                    'sound' => 'default',
                    'title' => $title,
                    'body' => $body,
                    'data' => $extraData,
                ]);
            } catch (\Exception $e) {
                Log::error("Expo Push Error ({$token}): " . $e->getMessage());
            }
        }

        // ✅ 2. Save to `notifications` table
        try {
            $notification = Notification::create([
                'title'     => $title,
                'message'   => $body,
                'type'      => $type,
                'event_id'  => $extraData['event_id'] ?? null,
            ]);

            // ✅ 3. Attach to each user in `user_notifications`
            $users = Users::all(); // or pwede mo i-filter kung sino lang dapat makatanggap

            foreach ($users as $user) {
                IamUserNotification::create([
                    'user_id'         => $user->id,                // ⚠ make sure 'id' is the primary key sa Users model
                    'notification_id' => $notification->id,
                    'is_read'         => false,
                    'delivered_at'    => now(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error saving notification to DB: ' . $e->getMessage());
        }
    }
}