<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SimpleBell extends Notification
{
    use Queueable;
    public function via($notifiable): array { return ['database','broadcast']; }
    public function toArray($n): array {
        return ['title' => 'Ping', 'body' => 'Bell test delivered live.', 'at' => now()->toIso8601String()];
    }
}
