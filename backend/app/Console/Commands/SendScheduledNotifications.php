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

        $nowUtc = now()->utc();

        $notifications = Notification::where('send_status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $nowUtc)
            ->orderBy('scheduled_at', 'asc')
            ->get();

        if ($notifications->isEmpty()) {
            $this->info('No scheduled notifications to process');
            return Command::SUCCESS;
        }

        foreach ($notifications as $notification) {

    $this->info("Processing notification ID: {$notification->id}");

    // ✅ FIXED: get ONLY assigned users
    $userIds = IamUserNotification::where('notification_id', $notification->id)
        ->pluck('user_id')
        ->toArray();

    foreach ($userIds as $uid) {

        IamUserNotification::where('user_id', $uid)
            ->where('notification_id', $notification->id)
            ->update([
                'delivered_at' => now(),
            ]);

        $unreadCount = IamUserNotification::where('user_id', $uid)
            ->where('is_read', false)
            ->whereNotNull('delivered_at')
            ->count();

        $tokens = DeviceUserToken::where('user_id', $uid)
            ->pluck('device_token')
            ->filter(fn ($t) => str_starts_with($t, 'ExponentPushToken'))
            ->toArray();

        foreach ($tokens as $token) {
            Http::post('https://exp.host/--/api/v2/push/send', [
                'to' => $token,
                'title' => $notification->title,
                'body' => $notification->message,
                'badge' => $unreadCount,
                'sound' => 'default',
            ]);
        }
    }

    $notification->update([
        'send_status' => 'sent',
    ]);
}

        return Command::SUCCESS;
    }
}