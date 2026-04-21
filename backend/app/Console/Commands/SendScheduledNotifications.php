<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\Mobile\Notification;
use App\Models\Mobile\IamUserNotification;
use App\Models\LwsisApp\DeviceUserToken;

class SendScheduledNotifications extends Command
{
    protected $signature = 'notifications:send-scheduled';
    protected $description = 'Send scheduled notifications';

    public function handle()
    {
        $this->info('Running scheduled notifications');
        Log::info('Running scheduled notifications');

        $nowUtc = Carbon::now('UTC');

        $notifications = Notification::where('send_status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $nowUtc)
            ->orderBy('scheduled_at', 'asc')
            ->get();

        if ($notifications->isEmpty()) {
            $this->info('No scheduled notifications to process');
            Log::info('No scheduled notifications to process', [
                'now_utc' => $nowUtc->toDateTimeString(),
            ]);

            return Command::SUCCESS;
        }

        foreach ($notifications as $notification) {
            $this->info("Processing notification ID: {$notification->id}");
            Log::info('Processing scheduled notification', [
                'notification_id' => $notification->id,
                'scheduled_at' => optional($notification->scheduled_at)->toDateTimeString(),
                'now_utc' => $nowUtc->toDateTimeString(),
            ]);

            try {
                $userNotifications = IamUserNotification::where('notification_id', $notification->id)->get();

                if ($userNotifications->isEmpty()) {
                    Log::warning('No iam_user_notifications found for scheduled notification', [
                        'notification_id' => $notification->id,
                    ]);

                    continue;
                }

                $sentTokens = 0;

                foreach ($userNotifications as $userNotification) {
                    $uid = $userNotification->user_id;

                    if (!$uid) {
                        Log::warning('Skipping scheduled notification row with missing user_id', [
                            'notification_id' => $notification->id,
                            'iam_user_notification_id' => $userNotification->id,
                        ]);
                        continue;
                    }

                    IamUserNotification::updateOrCreate(
                        [
                            'user_id' => $uid,
                            'notification_id' => $notification->id,
                        ],
                        [
                            'is_read' => false,
                            'delivered_at' => now(),
                        ]
                    );

                    $unreadCount = IamUserNotification::where('user_id', $uid)
                        ->where('is_read', false)
                        ->whereNotNull('delivered_at')
                        ->count();

                    $tokens = DeviceUserToken::where('user_id', $uid)
                        ->pluck('device_token')
                        ->filter(fn ($token) => is_string($token) && str_starts_with($token, 'ExponentPushToken'))
                        ->values()
                        ->toArray();

                    if (empty($tokens)) {
                        Log::info('No Expo tokens found for scheduled notification user', [
                            'notification_id' => $notification->id,
                            'user_id' => $uid,
                        ]);
                        continue;
                    }

                    foreach ($tokens as $token) {
                        $response = Http::post('https://exp.host/--/api/v2/push/send', [
                            'to'       => $token,
                            'sound'    => 'default',
                            'title'    => $notification->title,
                            'body'     => $notification->message,
                            'priority' => 'high',
                            'badge'    => $unreadCount,
                            'data'     => [
                                'notification_id' => $notification->id,
                                'user_id'         => $uid,
                            ],
                        ]);

                        Log::info('Expo push response for scheduled notification', [
                            'notification_id' => $notification->id,
                            'user_id' => $uid,
                            'token' => $token,
                            'status_code' => $response->status(),
                            'response' => $response->json(),
                        ]);

                        if ($response->successful()) {
                            $sentTokens++;
                        }
                    }
                }

                $notification->update([
                    'send_status' => 'sent',
                ]);

                Log::info('Scheduled notification marked as sent', [
                    'notification_id' => $notification->id,
                    'sent_tokens' => $sentTokens,
                ]);

            } catch (\Throwable $e) {
                Log::error('Failed processing scheduled notification', [
                    'notification_id' => $notification->id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}