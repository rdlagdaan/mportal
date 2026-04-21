<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Mobile\Event;
use App\Models\LwsisApp\UserEmployeeLink;
use App\Models\LwsisApp\HrEmployee;

class EventControllerr extends Controller
{
   public function index(Request $request)
{
    try {
        $events = Event::orderBy('event_date')
            ->orderBy('event_time')
            ->get();

        $result = [];

        foreach ($events as $event) {
            $result[] = [
                'id' => $event->event_id,
                'name' => $event->event_name,
                'description' => $event->event_description,
                'date' => optional($event->event_date)->toDateString(),

                // 👉 SAFE ACCESS (NO CRASH)
                'time' => $event->getRawOriginal('event_time'),
                'end_time' => $event->getRawOriginal('event_end_time'),

                'venue' => $event->event_venue,
                'participants' => $event->participants,

                // 👉 SAFE COMPUTATION
                'status' => $event->current_status,

                // 👉 SAFE IMAGE
                'image' => $event->event_image,
            ];
        }

        return response()->json($result);

    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function show($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return response()->json([
            'id' => $event->event_id,
            'name' => $event->event_name,
            'description' => $event->event_description,
            'date' => $event->event_date?->toDateString(),
            'time' => $event->event_time,
            'end_time' => $event->event_end_time,
            'venue' => $event->event_venue,
            'participants' => $event->participants,
            'status' => $event->current_status,
            'image' => $event->event_image,
        ]);
    }
}