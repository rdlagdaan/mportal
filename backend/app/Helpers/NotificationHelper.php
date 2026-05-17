<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Mobile\Notification;
use App\Models\Mobile\IamUserNotification;
use App\Models\LwsisApp\DeviceUserToken;

class NotificationHelper
{
    /**
     * Send push + save notification record + attach to user_notifications
     */
    public static function sendPushAndSaveToUsers(
        array $userIds,
        string $title,
        string $body,
        string $type = 'event',
        array $extraData = []
    ): ?Notification {
        try {
            $userIds = array_values(array_unique(array_filter($userIds)));

            if (empty($userIds)) {
                Log::warning('NotificationHelper: No user IDs provided.');
                return null;
            }

            // 1. Create notification once
            $notification = Notification::create([
                'title'        => $title,
                'message'      => $body,
                'type'         => $type,
                'event_id'     => $extraData['event_id'] ?? null,
                'created_by'   => null,
                'scheduled_at' => null,
                'send_status'  => 'sent',
            ]);

            // 2. Attach to each user
            foreach ($userIds as $uid) {
                IamUserNotification::updateOrCreate(
                    [
                        'user_id'         => $uid,
                        'notification_id' => $notification->id,
                    ],
                    [
                        'is_read'      => false,
                        'delivered_at' => now(),
                    ]
                );

                // unread badge count for this user
                $unreadCount = IamUserNotification::where('user_id', $uid)
                    ->where('is_read', false)
                    ->whereNotNull('delivered_at')
                    ->count();

                // get device tokens of this user
                $tokens = DeviceUserToken::where('user_id', $uid)
                    ->pluck('device_token')
                    ->filter()
                    ->values()
                    ->toArray();

                foreach ($tokens as $token) {
                    if (!str_starts_with($token, 'ExponentPushToken')) {
                        continue;
                    }

                    try {
                        $response = Http::post('https://exp.host/--/api/v2/push/send', [
                            'to'       => $token,
                            'sound'    => 'default',
                            'title'    => $title,
                            'body'     => $body,
                            'priority' => 'high',
                            'badge'    => $unreadCount,
                            'data'     => array_merge($extraData, [
                                'notification_id' => $notification->id,
                                'user_id'         => $uid,
                                'type'            => $type,
                            ]),
                        ]);

                        Log::info('Expo push sent', [
                            'user_id'  => $uid,
                            'token'    => $token,
                            'response' => $response->json(),
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Expo Push Error', [
                            'user_id' => $uid,
                            'token'   => $token,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return $notification;
        } catch (\Throwable $e) {
            Log::error('NotificationHelper sendPushAndSaveToUsers failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get recipient user IDs for events
     * Adjust mo ito later kung gusto mo specific participants lang.
     */
    public static function getEventRecipientUserIds(): array
    {
        return \DB::table('iam.users')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
    }
}

// namespace App\Helpers;

// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Log;
// use App\Models\Mobile\Notification;
// use App\Models\Mobile\IamUserNotification;
// use App\Models\LwsisApp\Users; 

// class NotificationHelper
// {
//     public static function sendPushAndSave(array $deviceTokens, string $title, string $body, string $type = 'event', array $extraData = []): void
//     {
//         // ✅ 1. Send push notification to eventExpo
//         foreach ($deviceTokens as $token) {
//             if (empty($token)) continue;

//             try {
//                 Http::post('https://exp.host/--/api/v2/push/send', [
//                     'to' => $token,
//                     'sound' => 'default',
//                     'title' => $title,
//                     'body' => $body,
//                     'data' => $extraData,
//                 ]);

//                 \Log::info('EXPO RESPONSE', [
//                     'token' => $token,
//                     'response' => $response->json(),
//                 ]);
//             } catch (\Exception $e) {
//                 Log::error("Expo Push Error ({$token}): " . $e->getMessage());
//             }
//         }

//         // ✅ 2. Save to `notifications` table
//         try {
//             $notification = Notification::create([
//                 'title'     => $title,
//                 'message'   => $body,
//                 'type'      => $type,
//                 'event_id'  => $extraData['event_id'] ?? null,
//             ]);

//             // ✅ 3. Attach to each user in `user_notifications`
//             $users = Users::all(); // or pwede mo i-filter kung sino lang dapat makatanggap

//             foreach ($users as $user) {
//                 IamUserNotification::create([
//                     'user_id'         => $user->id,                // ⚠ make sure 'id' is the primary key sa Users model
//                     'notification_id' => $notification->id,
//                     'is_read'         => false,
//                     'delivered_at'    => now(),
//                 ]);
//             }

//         } catch (\Exception $e) {
//             Log::error('Error saving notification to DB: ' . $e->getMessage());
//         }
//     }
// }