<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobile\Event;
use App\Helpers\NotificationHelper;

class EventControllerr extends Controller
{
    // ==============================
    // 📥 GET ALL EVENTS
    // ==============================
    public function index()
    {
        try {
            $events = Event::orderBy('event_date')
                ->orderBy('event_time')
                ->get();

            return response()->json(
                $events->map(function ($event) {
                    return [
                        'id'           => $event->event_id,
                        'name'         => $event->event_name,
                        'description'  => $event->event_description,
                        'date'         => optional($event->event_date)->toDateString(),
                        'time'         => $event->getRawOriginal('event_time'),
                        'end_time'     => $event->getRawOriginal('event_end_time'),
                        'venue'        => $event->event_venue,
                        'participants' => $event->participants,
                        'status'       => $event->current_status,
                        'image'        => $event->event_image,
                    ];
                })
            );

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==============================
    // 📄 GET SINGLE EVENT
    // ==============================
    public function show($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        return response()->json([
            'id'           => $event->event_id,
            'name'         => $event->event_name,
            'description'  => $event->event_description,
            'date'         => $event->event_date?->toDateString(),
            'time'         => $event->getRawOriginal('event_time'),
            'end_time'     => $event->getRawOriginal('event_end_time'),
            'venue'        => $event->event_venue,
            'participants' => $event->participants,
            'status'       => $event->current_status,
            'image'        => $event->event_image,
        ]);
    }

    // ==============================
    // 🆕 CREATE EVENT + PUSH
    // ==============================
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'event_name'        => 'required|string|max:255',
                'event_description' => 'required|string',
                'event_date'        => 'required|date',
                'event_time'        => 'required|string',
                'event_end_time'    => 'nullable|string',
                'event_venue'       => 'required|string|max:255',
                'participants'      => 'required|string|max:255',
                'event_image'       => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            // ✅ Upload image
            if ($request->hasFile('event_image')) {
                $validated['event_image'] = $request
                    ->file('event_image')
                    ->store('images', 'public');
            }

            // ✅ Default status
            $validated['event_status'] = 'upcoming';

            // ✅ Create event
            $event = Event::create($validated);

            // ==============================
            // 🔔 SEND NOTIFICATION
            // ==============================
            try {
                $userIds = NotificationHelper::getEventRecipientUserIds();

                if (!empty($userIds)) {
                    NotificationHelper::sendPushAndSaveToUsers(
                        $userIds,
                        "📢 New Event: {$event->event_name}",
                        $event->event_description,
                        'event',
                        [
                            'event_id' => $event->event_id,
                            'status'   => 'created',
                        ]
                    );
                }

            } catch (\Throwable $e) {
                \Log::error("Event push failed", [
                    'event_id' => $event->event_id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Event created successfully',
                'data'    => $event
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create event',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ==============================
    // ✏️ UPDATE EVENT
    // ==============================
    public function update(Request $request, $id)
    {
        try {
            $event = Event::findOrFail($id);

            $validated = $request->validate([
                'event_name'        => 'sometimes|string|max:255',
                'event_description' => 'nullable|string',
                'event_date'        => 'sometimes|date',
                'event_time'        => 'sometimes|string',
                'event_end_time'    => 'nullable|string',
                'event_venue'       => 'nullable|string|max:255',
                'participants'      => 'nullable|string|max:255',
                'event_image'       => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            ]);

            if ($request->hasFile('event_image')) {
                $validated['event_image'] = $request
                    ->file('event_image')
                    ->store('images', 'public');
            }

            $event->update($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Event updated successfully',
                'data'    => $event
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update event',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ==============================
    // 🗑 DELETE EVENT
    // ==============================
    public function destroy($id)
    {
        try {
            $event = Event::findOrFail($id);
            $event->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Event deleted successfully'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete event',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}