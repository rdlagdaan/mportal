<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Mobile\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\LwsisApp\DeviceUserToken;
use Illuminate\Support\Facades\Http;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */

    public function index()
{
    $events = Event::orderBy('event_date')
        ->orderBy('event_time')
        ->get()
        ->map(function ($event) {
            // Always compute current status
            $computedStatus = $event->current_status;

            // ✅ Update DB if different
            if ($event->event_status !== $computedStatus) {
                $event->event_status = $computedStatus;
                $event->save();
            }

            return [
                'id'            => $event->event_id,
                'name'          => $event->event_name,
                'description'   => $event->event_description,
                'date'          => $event->event_date?->toDateString(),
                'time'          => $event->event_time,
                'end_time'      => $event->event_end_time,
                'venue'         => $event->event_venue,
                'participants'  => $event->participants,
                'status'        => $computedStatus, // ✅ computed + saved in DB
                'image' => $event->event_image,

            ];
        });

    return response()->json($events);
}


    public function store(Request $request)
{
    $validated = $request->validate([
        'event_name'        => 'required|string|max:255',
       'event_description' => 'nullable|string',
        'event_date'        => 'required|date',
        'event_time'        => 'required|string',
        'event_end_time'    => 'nullable|string',
        'event_venue'       => 'required|string|max:255',
        'participants'      => 'required|string|max:255',
        'event_status'      => 'nullable|string|max:255',
        'event_image'       => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // max 5MB
    ]);

    // ✅ Step 1: Save event first
    if ($request->hasFile('event_image')) {
    $imagePath = $request->file('event_image')->store('images', 'public');

    // store only relative path
    $validated['event_image'] = $imagePath;
}
    // if ($request->hasFile('event_image')) {
    //     $imagePath = $request->file('event_image')->store('images', 'public');
    //     //$imagePath = $request->file('event_image')->store('events', 'public');
    //     $validated['event_image'] = \Storage::url($imagePath);
    // }



    $event = \App\Models\Mobile\Event::create($validated);

    // ✅ Step 2: Try sending notifications — but don’t fail if it errors
    try {
        $tokens = \App\Models\LwsisApp\DeviceUserToken::pluck('device_token')->toArray();

        if (!empty($tokens)) {
            foreach (array_chunk($tokens, 100) as $batch) {
                \Illuminate\Support\Facades\Http::post('https://exp.host/--/api/v2/push/send', [
                    'to'    => $batch,
                    'sound' => 'default',
                    'title' => 'New Event: ' . $event->event_name,
                    'body'  => $event->event_description,
                    'priority' => 'high',
                    'data'  => [
                        'event_id' => $event->event_id,
                        'type'     => 'event', // ✅ match frontend
                    ],
                ]);
            }
        }
    } catch (\Exception $e) {
        \Log::error('Expo push failed: ' . $e->getMessage());
    }

    // ✅ Step 3: Always return success
    return response()->json([
    'status'  => 'success',
    'message' => 'Event created successfully (notification sent if possible)',
    'data'    => $event,
], 201);

}


    /**
     * Display a specific event.
     */
    public function show($id)
    {
        $event = Event::findOrFail($id);

        return response()->json([
            'id'            => $event->event_id,
            'name'          => $event->event_name,
            'description'   => $event->event_description,
            'date'          => $event->event_date?->toDateString(),
            'time'          => $event->event_time,
            'end_time'      => $event->event_end_time,
            'venue'         => $event->event_venue,
            'participants'  => $event->participants,
            'status'        => $event->current_status, // ✅ dynamic
            'image' => $event->event_image,
        ]);
    }

    /**
     * Update a specific event.
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'event_name'        => 'sometimes|string|max:255',
            'event_description' => 'nullable|string|max:255',
            'event_date'        => 'sometimes|date',
            'event_time'        => 'sometimes|date_format:H:i',
            'event_end_time'    => 'sometimes|date_format:H:i|after:event_time',
            'event_venue'       => 'nullable|string|max:255',
            'participants'      => 'nullable|string|max:255',
            'event_image'       => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasFile('event_image')) {
            $file = $request->file('event_image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            // $path = $file->storeAs('public/events', $filename);
            $path = $request->file('event_image')->store('images','public');
            $validated['event_image'] = $path;
        }

        $event->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Event updated successfully',
            'data'    => $event
        ]);
    }

    /**
     * Soft delete an event.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Event deleted successfully'
        ]);
    }
}