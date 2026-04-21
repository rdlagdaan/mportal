<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Mobile\EnrollmentHistory;
use App\Models\Mobile\Notification;
use App\Models\Mobile\UserNotification;
use App\Models\Mobile\NotificationTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Mobile\UserIdentityLink;
use App\Models\Mobile\Users;
use Illuminate\Support\Facades\DB;
use App\Models\Mobile\DeviceToken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;     
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use App\Models\Mobile\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Mobile\EmployeeProfile;
use Illuminate\Support\Facades\Storage;
use Exception;



class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */

public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            'recipients' => 'required|array',
        ]);

        $title = $validated['title'];
        $message = $validated['message'];
        $recipients = $validated['recipients'];

        // 📝 1. Create notification record
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => 'general',
            'created_by' => null,
        ]);

        $userIds = [];


//         // 📎 Handle attachments (if any)
// if ($request->hasFile('attachments')) {
//     foreach ($request->file('attachments') as $file) {
//         $path = $file->store('notifications', 'public');

//         NotificationAttachment::create([
//             'notification_id' => $notification->id,
//             'type' => $file->getMimeType(),
//             'path' => $path,
//             'original_name' => $file->getClientOriginalName(),
//         ]);

//     }
// }

        // 🧍 2. Get specific user IDs
        $specificUsers = array_map('intval', array_filter($recipients, 'is_numeric'));

if (!empty($specificUsers)) {
    $userIds = $specificUsers;
} else {
    if (in_array('all_users', $recipients)) {
        $userIds = UserIdentityLink::pluck('user_id')->toArray();
    } elseif (in_array('students', $recipients)) {
        $userIds = UserIdentityLink::where('kind', 'student')->pluck('user_id')->toArray();
    } elseif (in_array('employees', $recipients)) {
        $userIds = UserIdentityLink::where('kind', 'employee')->pluck('user_id')->toArray();
    }
}
        $userIds = array_unique($userIds);
        $responses = [];
        $sentTokens = 0;

        // 🚀 3. Send notifications
        foreach ($userIds as $uid) {
            UserNotification::create([
                'user_id' => $uid,
                'notification_id' => $notification->id,
                'is_read' => false,
                'delivered_at' => now(),
            ]);

            $unreadCount = UserNotification::where('user_id', $uid)
                ->where('is_read', false)
                ->count();

            $tokens = DeviceToken::where('user_id', $uid)->pluck('device_token')->toArray();

            foreach ($tokens as $token) {
                if (str_starts_with($token, 'ExponentPushToken')) {
                    $res = Http::post("https://exp.host/--/api/v2/push/send", [
                        "to" => $token,
                        "sound" => "default",
                        "title" => $title,
                        "body" => $message,
                        'priority' => 'high',
                        "badge" => $unreadCount,
                        "data" => [
                            "notification_id" => $notification->id,
                            "user_id" => $uid,
                        ]
                    ]);
                    $responses[] = $res->json();
                    $sentTokens++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification created and sent',
            'notification_id' => $notification->id,
            'recipients_count' => count($userIds),
            'sent_to' => $sentTokens,
            'expo_responses' => $responses
        ]);

    } catch (\Exception $e) {
        \Log::error("Notification Error: " . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function index(Request $request)
{
    $user = $request->user();

    // Fetch notifications assigned to the user
    $userNotifications = UserNotification::with('notification')
        ->where('user_id', $user->id)
        ->get()
        ->map(function ($userNotification) {
            $n = $userNotification->notification;
            return [
                'id'         => $n->id,
                'title'      => $n->title,
                'message'    => $n->message,
                'type'       => $n->type,
                'read'       => (bool) $userNotification->is_read,
                'event_id'   => $n->event_id ?? null, // ✅ added
                'created_at' => $n->created_at?->toDateTimeString(),
                
            ];
        });

    // Fetch global event notifications
    $globalEvents = Notification::where('type', 'event')
        ->whereDoesntHave('userNotifications', fn($q) => $q->where('user_id', $user->id))
        ->get()
        ->map(fn($n) => [
            'id'         => $n->id,
            'title'      => $n->title,
            'message'    => $n->message,
            'type'       => $n->type,
            'read'       => (bool) $n->event_isread,
            'event_id'   => $n->event_id ?? null, // ✅ added
            'appointment_id' => $n->appointment_id ?? null,
            'created_at' => $n->created_at?->toDateTimeString(),
        ]);

    // Merge both lists
    $notifications = $userNotifications->merge($globalEvents)
        ->sortByDesc('created_at')
        ->values();

    return response()->json([
        'status' => 'success',
        'message' => 'Notifications fetched successfully',
        'notifications' => $notifications,
    ]);
}


    public function markAsRead(Request $request, $id)
{
    $user = $request->user();

    $userNotification = UserNotification::where('user_id', $user->id)
        ->where('notification_id', $id)
        ->first();

    if (!$userNotification) {
        return response()->json([
            'status' => 'error',
            'message' => 'Notification not found',
        ], 404);
    }

    // ✅ Mark as read
    $userNotification->is_read = true;
    $userNotification->save();

    // ✅ Recalculate unread count
    $unreadCount = UserNotification::where('user_id', $user->id)
        ->where('is_read', false)
        ->count();

    // ✅ Update badge via Expo
    $tokens = DeviceToken::where('user_id', $user->id)->pluck('device_token')->toArray();
    foreach ($tokens as $token) {
        if (str_starts_with($token, 'ExponentPushToken')) {
            Http::post("https://exp.host/--/api/v2/push/send", [
                "to" => $token,
                "badge" => $unreadCount, // 👈 set new badge count
                "data" => [
                    "notification_id" => $id,
                    "user_id" => $user->id
                ]
            ]);
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Notification marked as read',
        'unread_count' => $unreadCount, // return for frontend use too
    ]);
}


    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
{
    $user = $request->user();

    // ✅ Mark all as read
    UserNotification::where('user_id', $user->id)
        ->update(['is_read' => true]);

    // ✅ After marking all, unread is always 0
    $unreadCount = 0;

    // ✅ Silent badge update for iOS
    $tokens = DeviceToken::where('user_id', $user->id)->pluck('device_token')->toArray();
    foreach ($tokens as $token) {
        if (str_starts_with($token, 'ExponentPushToken')) {
            Http::post("https://exp.host/--/api/v2/push/send", [
                "to"    => $token,
                "badge" => $unreadCount,
                "data"  => [
                    "action" => "mark_all_as_read",
                    "user_id" => $user->id
                ]
            ]);
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'All notifications marked as read',
        'unread_count' => $unreadCount, // 👈 for Android/frontend
    ]);
}


    /**
 * Get a single notification for the authenticated user
 */
public function show(Request $request, $id)
{
    $user = $request->user();

    $userNotification = UserNotification::with('notification')
        ->where('user_id', $user->id)
        ->where('notification_id', $id)
        ->first();

    if (!$userNotification) {
        return response()->json([
            'status' => 'error',
            'message' => 'Notification not found',
        ], 404);
    }

    $n = $userNotification->notification;

    return response()->json([
    'id'             => $n->id,
    'title'          => $n->title,
    'message'        => $n->message,
    'type'           => $n->type,
    'read'           => (bool) $userNotification->is_read,
    'event_id'       => $n->event_id ?? null,
    'appointment_id' => $n->appointment_id ?? null, // ✅ added
    'created_at'     => $n->created_at?->toDateTimeString(),
]);

}

public function sendToCollege(Request $request)
{
    try {
        $request->validate([
            'college_code' => 'required|string',
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $collegeCode = $request->college_code;
        $title = $request->title;
        $message = $request->message;

        // ✅ 1️⃣ Get student_numbers from enrollment_history
        $studentNumbers = EnrollmentHistory::where('college_code', $collegeCode)
            ->pluck('student_number')
            ->toArray();

        if (empty($studentNumbers)) {
            return response()->json(['message' => 'No students found in this college.'], 404);
        }

        // ✅ 2️⃣ Get user_id from user_identity_links using student_number
        $userIds = UserIdentityLink::whereIn('student_number', $studentNumbers)
            ->pluck('user_id')
            ->toArray();

        if (empty($userIds)) {
            return response()->json(['message' => 'No users linked to these student numbers.'], 404);
        }

        // ✅ 3️⃣ Create notification record
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => 'college',
            'created_by' => null,
        ]);

        $responses = [];
        $totalTokens = 0;

        // ✅ 4️⃣ Create UserNotification + send push
        foreach ($userIds as $uid) {
            UserNotification::create([
                'user_id' => $uid,
                'notification_id' => $notification->id,
                'is_read' => false,
                'delivered_at' => now(),
            ]);

            $unreadCount = UserNotification::where('user_id', $uid)
                ->where('is_read', false)
                ->count();

            $tokens = DeviceToken::where('user_id', $uid)->pluck('device_token')->toArray();
            $totalTokens += count($tokens);

            foreach ($tokens as $token) {
                if (str_starts_with($token, 'ExponentPushToken')) {
                    $res = Http::post("https://exp.host/--/api/v2/push/send", [
                        "to" => $token,
                        "sound" => "default",
                        "title" => $title,
                        "body" => $message,
                        "badge" => $unreadCount,
                        'priority' => 'high',
                        "data" => [
                            "notification_id" => $notification->id,
                            "user_id" => $uid,
                            "college_code" => $collegeCode,
                        ],
                    ]);

                    $responses[] = $res->json();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "✅ Notification sent to " . count($userIds) . " students in $collegeCode",
            'notification_id' => $notification->id,
            'recipients_count' => count($userIds),
            'tokens_sent' => $totalTokens,
            'expo_responses' => $responses,
        ]);
    } catch (Exception $e) {
        Log::error('College Notification Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send college notification',
            'error' => $e->getMessage(),
        ], 500);
    }
}


 public function sendToOffice(Request $request)
    {
        try {
            $request->validate([
                'office_code' => 'required|string',
                'title'       => 'required|string|max:255',
                'message'     => 'required|string',
            ]);

            $officeCode = $request->office_code;
            $title      = $request->title;
            $message    = $request->message;

            // 1️⃣ Get employee_numbers from employee_profiles for this office
            $employeeNumbers = EmployeeProfile::where('college_office', $officeCode)
                ->pluck('employee_number')
                ->toArray();

            if (empty($employeeNumbers)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No employees found in this office.',
                ], 404);
            }

            // 2️⃣ Get user_ids from user_identity_links using employee_number
            $userIds = UserIdentityLink::whereIn('employee_number', $employeeNumbers)
                ->pluck('user_id')
                ->toArray();

            if (empty($userIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No users linked to employees in this office.',
                ], 404);
            }

            // 3️⃣ Create notification record
            $notification = Notification::create([
                'title'      => $title,
                'message'    => $message,
                'type'       => 'office',
                'created_by' => null,
            ]);

            $responses   = [];
            $totalTokens = 0;

            // 4️⃣ Create UserNotification + send Expo push for each user
            foreach ($userIds as $uid) {
                UserNotification::create([
                    'user_id'         => $uid,
                    'notification_id' => $notification->id,
                    'is_read'         => false,
                    'delivered_at'    => now(),
                ]);

                $unreadCount = UserNotification::where('user_id', $uid)
                    ->where('is_read', false)
                    ->count();

                $tokens = DeviceToken::where('user_id', $uid)
                    ->pluck('device_token')
                    ->toArray();
                $totalTokens += count($tokens);

                foreach ($tokens as $token) {
                    if (str_starts_with($token, 'ExponentPushToken')) {
                        $res = Http::post('https://exp.host/--/api/v2/push/send', [
                            'to'    => $token,
                            'sound' => 'default',
                            'title' => $title,
                            'body'  => $message,
                            'priority' => 'high',
                            'badge' => $unreadCount,
                            'data'  => [
                                'notification_id' => $notification->id,
                                'user_id'         => $uid,
                                'office_code'     => $officeCode,
                            ],
                        ]);

                        $responses[] = $res->json();
                    }
                }
            }

            return response()->json([
                'status'            => 'success',
                'message'           => "Notification sent to " . count($userIds) . " employees in $officeCode",
                'notification_id'   => $notification->id,
                'recipients_count'  => count($userIds),
                'tokens_sent'       => $totalTokens,
                'expo_responses'    => $responses,
            ]);
        } catch (Exception $e) {
            Log::error('Office Notification Error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send office notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



public function getEventDetails($eventId)
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json(['message' => 'Event not found'], 404);
            }

            return response()->json([
                'id'           => $event->event_id,
                'name'         => $event->event_name,
                'description'  => $event->event_description,
                'date'         => $event->event_date,
                'time'         => $event->event_time,
                'end_time'     => $event->event_end_time,
                'venue'        => $event->event_venue,
                'participants' => $event->participants,
                'status'       => $event->current_status,  // ✅ from accessor
                'image'        => $event->event_image,     // ✅ already full URL in model
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



}