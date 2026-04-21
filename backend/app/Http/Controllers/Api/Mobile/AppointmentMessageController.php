<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobile\AppointmentMessage;
use App\Models\Mobile\Appointment;
use App\Models\Mobile\DeviceToken;

class AppointmentMessageController extends Controller
{
    /**
     * 📩 Fetch all messages for an appointment
     */
    public function index($appointmentId)
    {
        try {
            $messages = AppointmentMessage::where('appointment_id', $appointmentId)
                ->with(['sender:id,name']) // eager load sender
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'messages' => $messages,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch messages: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load messages.',
            ], 500);
        }
    }

    /**
     * 💬 Store new message
     */


public function store(Request $request, $appointmentId)
{
    $request->validate([
        'message' => 'required|string',
    ]);

    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. No authenticated user found.',
            ], 401);
        }

        $appointment = \App\Models\Mobile\Appointment::find($appointmentId);
        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => "Appointment with ID {$appointmentId} not found.",
            ], 404);
        }

        // 💬 Save new message
        $message = new \App\Models\Mobile\AppointmentMessage();
        $message->appointment_id = $appointmentId;
        $message->sender_user_id = $user->id;
        $message->message = $request->message;
        $message->created_at = now();
        $message->save();

        $message->load('sender:id,name');

        // ✅ Broadcast new message in real time
        // broadcast(new \App\Events\MessageSent($message))->toOthers();
        \App\Events\MessageSent::dispatch($message);

        // 🎯 Identify receiver correctly
        if ($user->id == $appointment->student_user_id) {
            $receiverId = $appointment->employee_user_id;
        } elseif ($user->id == $appointment->employee_user_id) {
            $receiverId = $appointment->student_user_id;
        } else {
            // fallback (in case of missing data)
            $receiverId = $appointment->student_user_id ?? $appointment->employee_user_id;
        }

        // 🔍 Get all receiver tokens
        $tokens = \App\Models\Mobile\DeviceToken::where('user_id', $receiverId)
            ->pluck('device_token')
            ->toArray();

        $responses = [];
        $sentTokens = 0;

        // 🚀 Send Expo Push Notification
        foreach ($tokens as $token) {
            if (str_starts_with($token, 'ExponentPushToken')) {
                $payload = [
                    "to" => $token,
                    "sound" => "default",
                    "title" => $user->name,
                    "body" => $request->message,
                    "priority" => "high",   // 🔥 Add this
    "channelId" => "default", // ✅ matches your Android channel
                    "data" => [
                        "type" => "chat",
                        "appointment_id" => $appointmentId,
                        "sender_id" => $user->id,
                        "name" => $user->name, // 👈 added sender name
                        "title" => $appointment->appointment_title,
                    ],
                ];

                $res = \Http::post("https://exp.host/--/api/v2/push/send", $payload);
                $responses[] = $res->json();
                $sentTokens++;
            }
        }

        // 🧾 Log the push summary
        \Log::info('✅ Chat Push Notification Summary', [
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'tokens_count' => count($tokens),
            'sent_to' => $sentTokens,
            'expo_responses' => $responses,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $message,
            'sent_to' => $sentTokens,
            'expo_responses' => $responses,
        ]);

    } catch (\Throwable $e) {
        \Log::error('💥 AppointmentMessageController store error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send message.',
            'error' => $e->getMessage(),
        ], 500);
    }
}





}