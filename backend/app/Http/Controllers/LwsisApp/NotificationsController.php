<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

use App\Models\Mobile\Notification;
use App\Models\Mobile\IamUserNotification;
use App\Models\LwsisApp\DeviceUserToken;
use App\Models\Mobile\Event;
use App\Models\LwsisApp\Hris\HrEmployee;
use Carbon\Carbon;



class NotificationsController extends Controller
{
    /**
     * Create and send notification
     */
public function store(Request $request)
{
    try {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'send_mode' => 'nullable|in:now,scheduled',
            'scheduled_at' => 'nullable|date',
        ]);

        $title = $request->input('title');
        $message = $request->input('message');
        $sendMode = $request->input('send_mode', 'now');

        $scheduledAt = null;

        if ($sendMode === 'scheduled') {
            if (!$request->scheduled_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scheduled date/time is required',
                ], 400);
            }

            $scheduledAt = Carbon::parse($request->scheduled_at)
                ->setTimezone('UTC');
        }

        $recipients = (array) $request->input('recipients', []);
        $collegeIds = (array) $request->input('college_ids', []);
        $orgUnitIds = (array) $request->input('org_unit_ids', []);

        $recipients = array_filter($recipients);
        $collegeIds = array_filter($collegeIds);
        $orgUnitIds = array_filter($orgUnitIds);

        if (empty($recipients)) {
            $recipients = ['employees'];
        }

        $query = DB::table('iam.users')->where('is_active', true);

        $specificUsers = array_filter($recipients, fn ($r) => is_numeric($r));
        $userIds = [];

        if (!empty($specificUsers)) {

            $userIds = array_map('intval', $specificUsers);

        } elseif (in_array('students', $recipients)) {

            $query->where('user_type', 'student');

            if (!empty($collegeIds)) {
                $query->whereIn('college_id', $collegeIds);
            }

            $userIds = $query->pluck('id')->toArray();

        } elseif (in_array('employees', $recipients)) {

            $userIds = DB::table('iam.users as u')
                ->join('hris.hr_org_unit_memberships as m', 'u.id', '=', 'm.employee_id')
                ->select('u.id')
                ->where('u.is_active', true)
                ->where('m.is_active', true)
                ->where('u.user_type', 'employee')
                ->when(!empty($orgUnitIds), fn ($q) => $q->whereIn('m.org_unit_id', $orgUnitIds))
                ->distinct()
                ->pluck('u.id')
                ->toArray();

        } elseif (in_array('all_users', $recipients)) {

            $userIds = $query->pluck('id')->toArray();
        }

        $userIds = array_values(array_unique($userIds));

        if (empty($userIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No recipients found',
            ], 400);
        }

        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => 'general',
            'created_by' => null,
            'scheduled_at' => $scheduledAt,
            'send_status' => $sendMode === 'scheduled' ? 'pending' : 'sent',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('images', 'public');

                DB::table('mobile.notification_attachments')->insert([
                    'notification_id' => $notification->id,
                    'file_name'       => $file->getClientOriginalName(),
                    'file_path'       => $path,
                    'file_type'       => $file->getClientMimeType(),
                    'file_size'       => $file->getSize(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        foreach ($userIds as $uid) {
            IamUserNotification::updateOrCreate(
                [
                    'user_id' => $uid,
                    'notification_id' => $notification->id,
                ],
                [
                    'is_read' => false,
                    'delivered_at' => $sendMode === 'now' ? now() : null,
                ]
            );
        }

        if ($sendMode === 'scheduled') {
            return response()->json([
                'status' => 'success',
                'message' => 'Notification scheduled successfully',
                'notification_id' => $notification->id,
                'recipients_count' => count($userIds),
            ]);
        }

        $sentTokens = 0;

        foreach ($userIds as $uid) {
            $unreadCount = IamUserNotification::where('user_id', $uid)
                ->where('is_read', false)
                ->count();

            $tokens = DeviceUserToken::where('user_id', $uid)
                ->pluck('device_token')
                ->toArray();

            foreach ($tokens as $token) {
                if (str_starts_with($token, 'ExponentPushToken')) {
                    Http::post('https://exp.host/--/api/v2/push/send', [
                        'to'       => $token,
                        'sound'    => 'default',
                        'title'    => $title,
                        'body'     => $message,
                        'priority' => 'high',
                        'badge'    => $unreadCount,
                        'data'     => [
                            'notification_id' => $notification->id,
                            'user_id'         => $uid,
                        ],
                    ]);

                    $sentTokens++;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent successfully',
            'notification_id' => $notification->id,
            'recipients_count' => count($userIds),
            'sent_to' => $sentTokens,
        ]);

    } catch (\Throwable $e) {
        \Log::error('Notification Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Internal server error',
        ], 500);
    }
}

//     public function store(Request $request)
// {
//     try {
//         $request->validate([
//             'title' => 'required|string|max:255',
//             'message' => 'required|string',
//         ]);

//         $title = $request->input('title');
//         $message = $request->input('message');

//         //  NORMALIZE INPUTS (VERY IMPORTANT)
//         $recipients = $request->input('recipients', []);
//         $officeIds = $request->input('office_ids', []);
//         $collegeIds = $request->input('college_ids', []);

//         $recipients = is_array($recipients) ? $recipients : [$recipients];
//         $officeIds = is_array($officeIds) ? $officeIds : [$officeIds];
//         $collegeIds = is_array($collegeIds) ? $collegeIds : [$collegeIds];

//         //  CLEAN EMPTY VALUES
//         $recipients = array_filter($recipients);
//         $officeIds = array_filter($officeIds);
//         $collegeIds = array_filter($collegeIds);

//         //  FALLBACK (VERY IMPORTANT)
//         if (empty($recipients)) {
//             $recipients = ['employees'];
//         }

//         // 🧠 BUILD USER QUERY
//         $query = DB::table('iam.users')
//             ->where('is_active', true);

//         // 🎯 SPECIFIC USERS (priority)
//        $specificUsers = array_filter($recipients, fn($r) => is_numeric($r));

//         if (!empty($specificUsers)) {

//             $userIds = array_map('intval', $specificUsers);

//         } elseif (in_array('students', $recipients)) {

//             $query->where('user_type', 'student');

//             if (!empty($collegeIds)) {
//                 $query->whereIn('college_id', $collegeIds);
//             }

//             $userIds = $query->pluck('id')->toArray();

//         } elseif (in_array('employees', $recipients)) {

//             $orgUnitIds = $request->input('org_unit_ids', []);
//             $orgUnitIds = is_array($orgUnitIds) ? $orgUnitIds : [$orgUnitIds];

//             $userIds = DB::table('iam.users as u')
//                 ->join('hris.hr_org_unit_memberships as m', 'u.id', '=', 'm.employee_id')
//                 ->select('u.id')
//                 ->where('u.is_active', true)
//                 ->where('m.is_active', true)
//                 ->where('u.user_type', 'employee')
//                 ->when(!empty($orgUnitIds), function ($q) use ($orgUnitIds) {
//                     $q->whereIn('m.org_unit_id', $orgUnitIds);
//                 })
//                 ->distinct()
//                 ->pluck('u.id')
//                 ->toArray();

//         } elseif (in_array('all_users', $recipients)) {

//             $userIds = $query->pluck('id')->toArray();

//         }

//         //  REMOVE DUPLICATES
//         $userIds = array_values(array_unique($userIds));

//         // 🚨 SAFETY CHECK
//         if (empty($userIds)) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'No recipients found'
//             ], 400);
//         }

//         // 📝 CREATE NOTIFICATION
//         $notification = Notification::create([
//             'title' => $title,
//             'message' => $message,
//             'type' => 'general',
//             'created_by' => null,
//         ]);

//         $responses = [];
//         $sentTokens = 0;

//         // ==============================
//         // 📎 HANDLE FILE UPLOAD
//         // ==============================
//         if ($request->hasFile('attachments')) {

//             foreach ($request->file('attachments') as $file) {

//                  //$path = $file->store('notificationefile', 'public');
//                 // $path = $file->store('notificationefile', 'public');

//                 //temporary saved files to this path
//                 $path = $file->store('images', 'public');

//                 DB::table('mobile.notification_attachments')->insert([
//                     'notification_id' => $notification->id,
//                     'file_name'       => $file->getClientOriginalName(),
//                     'file_path'       => $path,
//                     'file_type'       => $file->getClientMimeType(),
//                     'file_size'       => $file->getSize(),
//                     'created_at'      => now(),
//                     'updated_at'      => now(),
//                 ]);
//             }
//         }

//         // 📤 SEND TO USERS
//         foreach ($userIds as $uid) {

//             IamUserNotification::updateOrCreate(
//                 [
//                     'user_id' => $uid,
//                     'notification_id' => $notification->id,
//                 ],
//                 [
//                     'is_read' => false,
//                     'delivered_at' => now(),
//                 ]
//             );

//             $unreadCount = IamUserNotification::where('user_id', $uid)
//                 ->where('is_read', false)
//                 ->count();

//             $tokens = DeviceUserToken::where('user_id', $uid)
//                 ->pluck('device_token')
//                 ->toArray();

//             foreach ($tokens as $token) {
//                 if (str_starts_with($token, 'ExponentPushToken')) {

//                     $res = Http::post('https://exp.host/--/api/v2/push/send', [
//                         'to'       => $token,
//                         'sound'    => 'default',
//                         'title'    => $title,
//                         'body'     => $message,
//                         'priority' => 'high',
//                         'badge'    => $unreadCount,
//                         'data'     => [
//                             'notification_id' => $notification->id,
//                             'user_id'         => $uid,
//                         ],
//                     ]);

//                     $responses[] = $res->json();
//                     $sentTokens++;
//                 }
//             }
//         }

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Notification sent successfully',
//             'notification_id' => $notification->id,
//             'recipients_count' => count($userIds),
//             'sent_to' => $sentTokens,
//         ]);

//     } catch (\Throwable $e) {

//         \Log::error('Notification Error', [
//             'message' => $e->getMessage(),
//             'trace' => $e->getTraceAsString(),
//         ]);

//         return response()->json([
//             'status' => 'error',
//             'message' => 'Internal server error',
//             'error' => $e->getMessage(), // optional remove in prod
//         ], 500);
//     }
// }
    // /**
    //  * Get all notifications for authenticated IAM user
    //  */
    public function index(Request $request)
    {
        $userId = $request->attributes->get('auth_user_id');

        $userNotifications = IamUserNotification::with('notification')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($item) {
                $n = $item->notification;

                if (!$n) {
                    return null;
                }

                return [
                    'id'             => $n->id,
                    'title'          => $n->title,
                    'message'        => $n->message,
                    'type'           => $n->type,
                    'read'           => (bool) $item->is_read,
                    'event_id'       => $n->event_id ?? null,
                    'appointment_id' => $n->appointment_id ?? null,
                    'created_at'     => $n->created_at?->toDateTimeString(),
                ];
            })
            ->filter()
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'status'        => 'success',
            'message'       => 'Notifications fetched successfully',
            'notifications' => $userNotifications,
        ]);
    }

    // /**
    //  * Mark one notification as read
    //  */
    public function markAsRead(Request $request, $id)
    {
        $userId = $request->attributes->get('auth_user_id');

        $userNotification = IamUserNotification::where('user_id', $userId)
            ->where('notification_id', $id)
            ->first();

        if (!$userNotification) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Notification not found',
            ], 404);
        }

        $userNotification->is_read = true;
        $userNotification->save();

        $unreadCount = IamUserNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        $tokens = DeviceUserToken::where('user_id', $userId)
            ->pluck('device_token')
            ->toArray();

        foreach ($tokens as $token) {
            if (str_starts_with($token, 'ExponentPushToken')) {
                Http::post('https://exp.host/--/api/v2/push/send', [
                    'to'    => $token,
                    'badge' => $unreadCount,
                    'data'  => [
                        'notification_id' => $id,
                        'user_id'         => $userId,
                    ],
                ]);
            }
        }

        return response()->json([
            'status'       => 'success',
            'message'      => 'Notification marked as read',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $userId = $request->attributes->get('auth_user_id');

        IamUserNotification::where('user_id', $userId)
            ->update(['is_read' => true]);

        $unreadCount = 0;

        $tokens = DeviceUserToken::where('user_id', $userId)
            ->pluck('device_token')
            ->toArray();

        foreach ($tokens as $token) {
            if (str_starts_with($token, 'ExponentPushToken')) {
                Http::post('https://exp.host/--/api/v2/push/send', [
                    'to'    => $token,
                    'badge' => $unreadCount,
                    'data'  => [
                        'action'  => 'mark_all_as_read',
                        'user_id' => $userId,
                    ],
                ]);
            }
        }

        return response()->json([
            'status'       => 'success',
            'message'      => 'All notifications marked as read',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get single notification
     */
    public function show(Request $request, $id)
{
    $userId = $request->attributes->get('auth_user_id');

    $userNotification = IamUserNotification::with('notification')
        ->where('user_id', $userId)
        ->where('notification_id', $id)
        ->first();

    if (!$userNotification) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Notification not found',
        ], 404);
    }

    $n = $userNotification->notification;

    $attachments = DB::table('mobile.notification_attachments')
        ->where('notification_id', $n->id)
        ->get()
        ->map(function ($file) {
            $file->url = url('storage/' . $file->file_path);
            return $file;
        });

    return response()->json([
        'id'             => $n->id,
        'title'          => $n->title,
        'message'        => $n->message,
        'type'           => $n->type,
        'read'           => (bool) $userNotification->is_read,
        'event_id'       => $n->event_id ?? null,
        'appointment_id' => $n->appointment_id ?? null,
        'created_at'     => $n->created_at?->toDateTimeString(),
        'attachments'    => $attachments->values(),
    ]);
}
  
    /**
     * Send notification to all student IAM users
     */
    public function sendToStudents(Request $request)
    {
        try {
            $request->validate([
                'title'   => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            $title = $request->title;
            $message = $request->message;

            $userIds = DB::table('iam.users')
                ->where('is_active', true)
                ->where('user_type', 'student')
                ->pluck('id')
                ->toArray();

            if (empty($userIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No student users found.',
                ], 404);
            }

            $notification = Notification::create([
                'title'      => $title,
                'message'    => $message,
                'type'       => 'college',
                'created_by' => null,
            ]);

            $responses = [];
            $totalTokens = 0;

            foreach ($userIds as $uid) {
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
                    ->count();

                $tokens = DeviceUserToken::where('user_id', $uid)
                    ->pluck('device_token')
                    ->toArray();

                $totalTokens += count($tokens);

                foreach ($tokens as $token) {
                    if (str_starts_with($token, 'ExponentPushToken')) {
                        $res = Http::post('https://exp.host/--/api/v2/push/send', [
                            'to'       => $token,
                            'sound'    => 'default',
                            'title'    => $title,
                            'body'     => $message,
                            'badge'    => $unreadCount,
                            'priority' => 'high',
                            'data'     => [
                                'notification_id' => $notification->id,
                                'user_id'         => $uid,
                                'audience'        => 'students',
                            ],
                        ]);

                        $responses[] = $res->json();
                    }
                }
            }

            return response()->json([
                'status'           => 'success',
                'message'          => 'Notification sent to student users',
                'notification_id'  => $notification->id,
                'recipients_count' => count($userIds),
                'tokens_sent'      => $totalTokens,
                'expo_responses'   => $responses,
            ]);
        } catch (Exception $e) {
            Log::error('Student Notification Error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send student notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send notification to all employee IAM users
     */
    public function sendToEmployees(Request $request)
    {
        try {
            $request->validate([
                'title'   => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            $title = $request->title;
            $message = $request->message;

            $userIds = DB::table('iam.users')
                ->where('is_active', true)
                ->where('user_type', 'employee')
                ->pluck('id')
                ->toArray();

            if (empty($userIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No employee users found.',
                ], 404);
            }

            $notification = Notification::create([
                'title'      => $title,
                'message'    => $message,
                'type'       => 'office',
                'created_by' => null,
            ]);

            $responses = [];
            $totalTokens = 0;

            foreach ($userIds as $uid) {
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
                    ->count();

                $tokens = DeviceUserToken::where('user_id', $uid)
                    ->pluck('device_token')
                    ->toArray();

                $totalTokens += count($tokens);

                foreach ($tokens as $token) {
                    if (str_starts_with($token, 'ExponentPushToken')) {
                        $res = Http::post('https://exp.host/--/api/v2/push/send', [
                            'to'       => $token,
                            'sound'    => 'default',
                            'title'    => $title,
                            'body'     => $message,
                            'priority' => 'high',
                            'badge'    => $unreadCount,
                            'data'     => [
                                'notification_id' => $notification->id,
                                'user_id'         => $uid,
                                'audience'        => 'employees',
                            ],
                        ]);

                        $responses[] = $res->json();
                    }
                }
            }

            return response()->json([
                'status'           => 'success',
                'message'          => 'Notification sent to employee users',
                'notification_id'  => $notification->id,
                'recipients_count' => count($userIds),
                'tokens_sent'      => $totalTokens,
                'expo_responses'   => $responses,
            ]);
        } catch (Exception $e) {
            Log::error('Employee Notification Error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send employee notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send notification to specific org unit employees
     */
    public function sendToOrgUnit(Request $request)
    {
        try {
            $request->validate([
                'org_unit_id' => 'required|integer',
                'title'       => 'required|string|max:255',
                'message'     => 'required|string',
            ]);

            $orgUnitId = $request->org_unit_id;
            $title = $request->title;
            $message = $request->message;

            // $employeeIds = DB::table('hris.hr_employees')
            //     ->where('org_unit_id', $orgUnitId)
            //     ->where('is_active', true)
            //     ->pluck('id')
            //     ->toArray();

            $employeeIds = DB::table('hris.hr_org_unit_memberships')
                ->where('org_unit_id', $orgUnitId)
                ->where('is_active', true)
                ->pluck('employee_id')
                ->toArray();

            if (empty($employeeIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No employees found in this org unit.',
                ], 404);
            }

            $userIds = DB::table('iam.user_employee_links')
                ->whereIn('employee_id', $employeeIds)
                ->pluck('user_id')
                ->toArray();

            if (empty($userIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No IAM users linked to employees in this org unit.',
                ], 404);
            }

            $notification = Notification::create([
                'title'      => $title,
                'message'    => $message,
                'type'       => 'office',
                'created_by' => null,
            ]);

            $responses = [];
            $totalTokens = 0;

            foreach ($userIds as $uid) {
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
                    ->count();

                $tokens = DeviceUserToken::where('user_id', $uid)
                    ->pluck('device_token')
                    ->toArray();

                $totalTokens += count($tokens);

                foreach ($tokens as $token) {
                    if (str_starts_with($token, 'ExponentPushToken')) {
                        $res = Http::post('https://exp.host/--/api/v2/push/send', [
                            'to'       => $token,
                            'sound'    => 'default',
                            'title'    => $title,
                            'body'     => $message,
                            'priority' => 'high',
                            'badge'    => $unreadCount,
                            'data'     => [
                                'notification_id' => $notification->id,
                                'user_id'         => $uid,
                                'org_unit_id'     => $orgUnitId,
                            ],
                        ]);

                        $responses[] = $res->json();
                    }
                }
            }

            return response()->json([
                'status'           => 'success',
                'message'          => 'Notification sent to org unit users',
                'notification_id'  => $notification->id,
                'recipients_count' => count($userIds),
                'tokens_sent'      => $totalTokens,
                'expo_responses'   => $responses,
            ]);
        } catch (Exception $e) {
            Log::error('Org Unit Notification Error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send org unit notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get event details from notification
     */
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
                'status'       => $event->current_status,
                'image'        => $event->event_image,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Server error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function sendToCollege(Request $request)
{
    try {
        $request->validate([
            'college_code' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $collegeCode = $request->college_code;
        $title = $request->title;
        $message = $request->message;

        //  Get student numbers
        $studentNumbers = DB::table('mobile.enrollment_history')
            ->where('college_code', $collegeCode)
            ->pluck('student_number')
            ->toArray();

        if (empty($studentNumbers)) {
            return response()->json(['message' => 'No students found'], 404);
        }

        //  Map to IAM users
        $userIds = DB::table('iam.user_identity_links')
            ->whereIn('student_number', $studentNumbers)
            ->pluck('user_id')
            ->toArray();

        if (empty($userIds)) {
            return response()->json(['message' => 'No linked users'], 404);
        }

        //  Create notification
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => 'college',
            'created_by' => null,
        ]);

        foreach ($userIds as $uid) {
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
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent to college',
            'recipients_count' => count($userIds),
        ]);

    } catch (Exception $e) {
        Log::error('College Error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
public function sendToOffice(Request $request)
{
    try {
        $request->validate([
            'office_code' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $officeCode = $request->office_code;
        $title = $request->title;
        $message = $request->message;

        //  Get employees
        $employeeNumbers = DB::table('mobile.employee_profiles')
            ->where('college_office', $officeCode)
            ->pluck('employee_number')
            ->toArray();

        if (empty($employeeNumbers)) {
            return response()->json(['message' => 'No employees found'], 404);
        }

        //  Map to IAM users
        $userIds = DB::table('iam.user_employee_links')
            ->whereIn('employee_id', $employeeNumbers)
            ->pluck('user_id')
            ->toArray();

        if (empty($userIds)) {
            return response()->json(['message' => 'No linked users'], 404);
        }

        //  Create notification
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'type' => 'office',
            'created_by' => null,
        ]);

        foreach ($userIds as $uid) {
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
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent to office',
            'recipients_count' => count($userIds),
        ]);

    } catch (Exception $e) {
        Log::error('Office Error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
// public function unreadCount(Request $request)
// {
//     $userId = $request->attributes->get('auth_user_id');

//     if (!$userId) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Unauthorized',
//         ], 401);
//     }

//     $count = IamUserNotification::where('user_id', $userId)
//         ->where('is_read', false)
//         ->count();

//     return response()->json([
//         'status' => 'success',
//         'count' => $count,
//     ]);
// }
}