<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Mobile\UserIdentityLink;
use App\Http\Controllers\Controller;
use App\Models\Mobile\Notification;
use App\Models\Mobile\UserNotification;
use Illuminate\Support\Facades\Http;
use App\Models\Mobile\DeviceToken;

class AppointmentController extends Controller
{
    public function getEmployees()
    {
        $employees = DB::table('user_identity_links')
            ->join('users', 'user_identity_links.user_id', '=', 'users.id')
            ->join('mobile.employee_profiles', 'user_identity_links.employee_number', '=', 'mobile.employee_profiles.employee_number')
            ->where('user_identity_links.kind', 'employee')
            ->select(
                'users.id as user_id',
                'mobile.employee_profiles.employee_number',
                DB::raw("CONCAT(mobile.employee_profiles.first_name, ' ', COALESCE(mobile.employee_profiles.middle_name, ''), ' ', mobile.employee_profiles.last_name) as full_name"),
                'mobile.employee_profiles.employee_position',
                'mobile.employee_profiles.college_office_desc'
            )
            ->orderBy('mobile.employee_profiles.last_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'employees' => $employees
        ]);
    }

    public function book(Request $request)
    {
        $user = $request->user();
        $identity = UserIdentityLink::where('user_id', $user->id)->first();

        if (!$identity || $identity->kind !== 'student') {
            return response()->json(['status' => 'error', 'message' => 'Only students can book appointments.'], 403);
        }

        $request->validate([
            'employee_user_id' => 'required|integer|exists:users,id',
            'appointment_title' => 'required|string|max:255', // ✅ new validation
            'purpose' => 'required|string|max:255',
            'appointment_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Save appointment with title
            $appointmentId = DB::table('mobile.appointments')->insertGetId([
                'student_user_id' => $user->id,
                'employee_user_id' => $request->employee_user_id,
                'appointment_title' => $request->appointment_title, // ✅ added
                'purpose' => $request->purpose,
                'appointment_date' => $request->appointment_date,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2️⃣ Create notification
            $notification = Notification::create([
                'title' => 'New Appointment Request',
                'message' => "{$user->name} booked an appointment with you titled \"{$request->appointment_title}\" on {$request->appointment_date} for \"{$request->purpose}\".",
                'type' => 'appointment',
                'appointment_id' => $appointmentId,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            // 3️⃣ Link notification to employee
            UserNotification::create([
                'user_id' => $request->employee_user_id,
                'notification_id' => $notification->id,
                'is_read' => false,
                'delivered_at' => now(),
            ]);

            DB::commit();

            // 4️⃣ Get employee device tokens
            $tokens = DeviceToken::where('user_id', $request->employee_user_id)
                ->pluck('device_token')
                ->toArray();

            // 5️⃣ Send Expo push notification
            if (!empty($tokens)) {
                foreach ($tokens as $token) {
                    Http::post('https://exp.host/--/api/v2/push/send', [
                        'to' => $token,
                        'sound' => 'default',
                        'title' => 'New Appointment Request',
                        'body' => "{$user->name} booked an appointment titled \"{$request->appointment_title}\" on {$request->appointment_date}.",
                        'priority' => 'high',
                        'data' => [
                            'type' => 'appointment',
                            'appointment_id' => $appointmentId,
                            'student_id' => $user->id,
                            'appointment_date' => $request->appointment_date,
                            'notification_id' => $notification->id, // 👈 add this
                            
                        ],
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Appointment booked successfully and notification sent.',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Booking failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAppointments(Request $request)
    {
        $user = $request->user();
        $identity = UserIdentityLink::where('user_id', $user->id)->first();

        if (!$identity) {
            return response()->json(['status' => 'error', 'message' => 'User identity not found.'], 404);
        }

        if ($identity->kind === 'employee') {
            // 👨‍💼 Employee: see appointments made with them
            $appointments = DB::table('mobile.appointments')
                ->join('users as students', 'mobile.appointments.student_user_id', '=', 'students.id')
                ->join('user_identity_links as sil', 'students.id', '=', 'sil.user_id')
                ->join('mobile.student_profiles', 'sil.student_number', '=', 'mobile.student_profiles.student_number')
                ->where('employee_user_id', $user->id)
                ->select(
                    'mobile.appointments.id',
                    'mobile.appointments.appointment_title', // ✅ include title
                    DB::raw("CONCAT(mobile.student_profiles.first_name, ' ', COALESCE(mobile.student_profiles.middle_name, ''), ' ', mobile.student_profiles.last_name) as student_name"),
                    'sil.student_number as student_number',
                    'mobile.appointments.purpose',
                    'mobile.appointments.appointment_date',
                    'mobile.appointments.status'
                )
                ->orderBy('mobile.appointments.appointment_date', 'desc')
                ->get();
        } else {
            // 👨‍🎓 Student: see appointments they booked
            $appointments = DB::table('mobile.appointments')
                ->join('users as employees', 'mobile.appointments.employee_user_id', '=', 'employees.id')
                ->join('user_identity_links as eil', 'employees.id', '=', 'eil.user_id')
                ->join('mobile.employee_profiles', 'eil.employee_number', '=', 'mobile.employee_profiles.employee_number')
                ->where('student_user_id', $user->id)
                ->select(
                    'mobile.appointments.id',
                    'mobile.appointments.appointment_title', // ✅ include title
                    DB::raw("CONCAT(mobile.employee_profiles.first_name, ' ', COALESCE(mobile.employee_profiles.middle_name, ''), ' ', mobile.employee_profiles.last_name) as employee_name"),
                    'mobile.employee_profiles.employee_position',
                    'mobile.appointments.purpose',
                    'mobile.appointments.appointment_date',
                    'mobile.appointments.status'
                )
                ->orderBy('mobile.appointments.appointment_date', 'desc')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'appointments' => $appointments
        ]);
    }
public function updateStatus(Request $request, $id)
{
    $user = $request->user();
    $identity = UserIdentityLink::where('user_id', $user->id)->first();

    $request->validate([
        'status' => 'required|string|in:pending,approved,declined,cancelled,done',
    ]);

    $appointment = DB::table('mobile.appointments')->where('id', $id)->first();

    if (!$appointment) {
        return response()->json([
            'status' => 'error',
            'message' => 'Appointment not found.'
        ], 404);
    }

    // -------------------------------------------------
    // 🔒 BLOCK UPDATES IF APPOINTMENT IS ALREADY CLOSED
    // -------------------------------------------------
    if (in_array($appointment->status, ['done', 'declined', 'cancelled'])) {
        return response()->json([
            'status' => 'error',
            'message' => 'Cannot modify a completed or closed appointment.'
        ], 403);
    }

    // -------------------------------------------------
    // 🧑‍🎓 STUDENT — CAN ONLY CANCEL THEIR OWN APPOINTMENT
    // -------------------------------------------------
    if ($identity && $identity->kind === 'student') {
        if ($appointment->student_user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 403);
        }

        if ($request->status !== 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'Students can only cancel appointments.'
            ], 403);
        }

        // ✅ Allow student to cancel
        DB::table('mobile.appointments')->where('id', $id)->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Appointment cancelled successfully.",
        ]);
    }

    // -------------------------------------------------
    // 🧑‍💼 EMPLOYEE — CAN UPDATE ANY VALID STATUS
    // -------------------------------------------------
    if (!$identity || $identity->kind !== 'employee') {
        return response()->json([
            'status' => 'error',
            'message' => 'Only employees can update appointment status.'
        ], 403);
    }

    // Ensure employee owns this appointment
    $updated = DB::table('mobile.appointments')
        ->where('id', $id)
        ->where('employee_user_id', $user->id)
        ->update([
            'status' => $request->status,
            'updated_at' => now(),
        ]);

    if (!$updated) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized or failed to update.'
        ], 403);
    }

    // -------------------------------------------------
    // 🔔 SEND NOTIFICATIONS (EMPLOYEE → STUDENT)
    // -------------------------------------------------
    $studentId = $appointment->student_user_id;

    $statusText = ucfirst($request->status);
    $title = 'Appointment Status Update';
    $message = match ($request->status) {
        'approved'  => 'Your appointment has been approved.',
        'declined'  => 'Your appointment has been declined.',
        'cancelled' => 'Your appointment has been cancelled.',
        'done'      => 'Your appointment has been marked as done.',
        default     => "Your appointment status has been updated to {$statusText}.",
    };

    DB::beginTransaction();

    try {
        $notification = Notification::create([
            'title'          => $title,
            'message'        => $message,
            'type'           => 'appointment',
            'appointment_id' => $appointment->id,
            'created_by'     => $user->id,
            'event_isread'   => false,
            'created_at'     => now(),
        ]);

        UserNotification::create([
            'user_id'         => $studentId,
            'notification_id' => $notification->id,
            'is_read'         => false,
            'delivered_at'    => now(),
        ]);

        DB::commit();

        $tokens = DeviceToken::where('user_id', $studentId)
            ->pluck('device_token')
            ->toArray();

        if (!empty($tokens)) {
            foreach ($tokens as $token) {
                Http::post('https://exp.host/--/api/v2/push/send', [
                    'to'    => $token,
                    'sound' => 'default',
                    'title' => $title,
                    'body'  => $message,
                    'priority' => 'high',
                    'data'  => [
                        'type'            => 'appointment',
                        'notification_id' => $notification->id,
                        'appointment_id'  => $appointment->id,
                        'status'          => $request->status,
                    ],
                ]);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Appointment status updated to '{$request->status}'.",
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to update appointment: ' . $e->getMessage(),
        ], 500);
    }
}

    public function getAppointmentById($id, Request $request)
    {
        $user = $request->user();
        $identity = UserIdentityLink::where('user_id', $user->id)->first();

        if (!$identity) {
            return response()->json([
                'status' => 'error',
                'message' => 'User identity not found.'
            ], 404);
        }

        $query = DB::table('mobile.appointments')
            ->where('mobile.appointments.id', $id);

        if ($identity->kind === 'employee') {
            $query->join('users as students', 'mobile.appointments.student_user_id', '=', 'students.id')
                ->join('user_identity_links as sil', 'students.id', '=', 'sil.user_id')
                ->join('mobile.student_profiles', 'sil.student_number', '=', 'mobile.student_profiles.student_number')
                ->select(
                    'mobile.appointments.id',
                    'mobile.appointments.appointment_title', // ✅ include title
                    DB::raw("CONCAT(mobile.student_profiles.first_name, ' ', COALESCE(mobile.student_profiles.middle_name, ''), ' ', mobile.student_profiles.last_name) as student_name"),
                    'mobile.student_profiles.student_number',
                    'mobile.appointments.purpose',
                    'mobile.appointments.appointment_date',
                    'mobile.appointments.status',
                    'mobile.appointments.created_at',
                    'mobile.appointments.updated_at'
                )
                ->where('mobile.appointments.employee_user_id', $user->id);
        } else {
            $query->join('users as employees', 'mobile.appointments.employee_user_id', '=', 'employees.id')
                ->join('user_identity_links as eil', 'employees.id', '=', 'eil.user_id')
                ->join('mobile.employee_profiles', 'eil.employee_number', '=', 'mobile.employee_profiles.employee_number')
                ->select(
                    'mobile.appointments.id',
                    'mobile.appointments.appointment_title', // ✅ include title
                    DB::raw("CONCAT(mobile.employee_profiles.first_name, ' ', COALESCE(mobile.employee_profiles.middle_name, ''), ' ', mobile.employee_profiles.last_name) as employee_name"),
                    'mobile.employee_profiles.employee_position',
                    'mobile.employee_profiles.college_office_desc',
                    'mobile.appointments.purpose',
                    'mobile.appointments.appointment_date',
                    'mobile.appointments.status',
                    'mobile.appointments.created_at',
                    'mobile.appointments.updated_at'
                )
                ->where('mobile.appointments.student_user_id', $user->id);
        }

        $appointment = $query->first();

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Appointment not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'appointment' => $appointment
        ]);
    }
}

