<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mobile\Event;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CheckEventNotifications extends Command
{
    protected $signature = 'events:check-notifications';
    protected $description = 'Handle event reminder, ongoing, and ended notifications';

    public function handle()
    {
        $now = now('Asia/Manila');

        $events = Event::whereBetween('event_date', [
    $now->copy()->subDay()->toDateString(),
    $now->copy()->addDay()->toDateString()
])->get();

        foreach ($events as $event) {
            try {
                if (!$event->event_date || !$event->getRawOriginal('event_time')) {
                    continue;
                }

                $date = $event->event_date->toDateString();

                $start = Carbon::parse(
                    "{$date} {$event->getRawOriginal('event_time')}",
                    'Asia/Manila'
                );

                $end = $event->getRawOriginal('event_end_time')
                    ? Carbon::parse(
                        "{$date} {$event->getRawOriginal('event_end_time')}",
                        'Asia/Manila'
                    )
                    : (clone $start)->addHour();

                $userIds = NotificationHelper::getEventRecipientUserIds();

                if (empty($userIds)) {
                    continue;
                }

                // =========================
                // ⏰ REMINDER (5 mins before)
                // =========================
                $reminderTime = (clone $start)->subMinutes(5);
                $keyReminder = "event_reminder_{$event->event_id}";

                $diff = $now->diffInSeconds($start, false);

                    if (
                        $diff <= 300 &&
                        $diff >= 240 &&
                        !Cache::has($keyReminder)
                    ) {
                    NotificationHelper::sendPushAndSaveToUsers(
                        $userIds,
                        "⏰ Event Reminder: {$event->event_name}",
                        "Event {$event->event_name} starts in 5 minutes.",
                        'event',
                        [
                            'event_id' => $event->event_id,
                            'status'   => 'reminder',
                        ]
                    );

                    Cache::put($keyReminder, true, now()->addMinutes(10));

                    \Log::info("Reminder sent", ['event_id' => $event->event_id]);
                }

                // =========================
                // 🚀 ONGOING
                // =========================
                $keyOngoing = "event_ongoing_{$event->event_id}";

                if (
                    $now->between($start, $end) &&
                    !Cache::has($keyOngoing)
                ) {
                    if ($event->event_status !== 'ongoing') {
                        $event->timestamps = false;
                        $event->update(['event_status' => 'ongoing']);
                    }

                    Cache::put($keyOngoing, true, now()->addHours(2));

                    \Log::info("Event ongoing", ['event_id' => $event->event_id]);
                }

                // =========================
                // ✅ DONE
                // =========================
                $keyDone = "event_done_{$event->event_id}";

                if (
                    $now->greaterThanOrEqualTo($end) &&
                    !Cache::has($keyDone)
                ) {
                    if ($event->event_status !== 'done') {
                        $event->timestamps = false;
                        $event->update(['event_status' => 'done']);
                    }

                    Cache::put($keyDone, true, now()->addHours(24));

                    \Log::info("Event ended", ['event_id' => $event->event_id]);
                }

            } catch (\Throwable $e) {
                \Log::error('Event scheduler error', [
                    'event_id' => $event->event_id ?? null,
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        $this->info('Event notifications processed.');
        return Command::SUCCESS;
    }
}