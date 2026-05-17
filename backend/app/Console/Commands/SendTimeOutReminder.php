<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\LwsisApp\User;
use App\Models\Mobile\DailyTimeRecord;
use App\Models\LwsisApp\Hris\HrEmployeeWeeklyScheduleRequest;

class SendTimeOutReminder extends Command
{
    protected $signature = 'dtr:timeout-reminder';

    protected $description =
        'Send push notification when shift end is reached';

    public function handle()
    {
        $now = Carbon::now();

        $today = $now->toDateString();

        /*
        |--------------------------------------------------------------------------
        | GET ACTIVE EMPLOYEE SCHEDULES
        |--------------------------------------------------------------------------
        */

        $schedules =
            HrEmployeeWeeklyScheduleRequest::with('days')
                ->where('status', 'APPROVED')
                ->where('is_active_pattern', true)
                ->get();

        foreach ($schedules as $schedule) {

            $todayDay =
                $schedule->days
                    ->where(
                        'day_of_week',
                        $now->dayOfWeekIso
                    )
                    ->first();

            if (
                !$todayDay ||
                !$todayDay->is_workday
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | CHECK IF CURRENT TIME MATCHES SHIFT END
            |--------------------------------------------------------------------------
            */

            $shiftEnd =
                Carbon::parse(
                    $today . ' ' . $todayDay->time_out
                );

            /*
            |--------------------------------------------------------------------------
            | ONLY SEND ON EXACT MINUTE
            |--------------------------------------------------------------------------
            */

            if (
                $now->format('H:i') !==
                $shiftEnd->format('H:i')
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | FIND USER
            |--------------------------------------------------------------------------
            */

            $employeeId = $schedule->employee_id;

            $userId =
                DB::table('iam.user_employee_links')
                    ->where('employee_id', $employeeId)
                    ->value('user_id');

            if (!$userId) {
                continue;
            }

            $user = User::find($userId);

            if (!$user) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | CHECK DTR
            |--------------------------------------------------------------------------
            */

            $dtr =
                DailyTimeRecord::where(
                        'user_id',
                        $user->id
                    )
                    ->whereDate(
                        'work_date',
                        $today
                    )
                    ->first();

            /*
            |--------------------------------------------------------------------------
            | MUST HAVE TIMED IN
            |--------------------------------------------------------------------------
            */

            if (
                !$dtr ||
                !$dtr->time_in ||
                $dtr->time_out
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | SEND PUSH
            |--------------------------------------------------------------------------
            */

            $tokens =
                DB::table('device_push_tokens')
                    ->where('user_id', $user->id)
                    ->pluck('expo_push_token');

            foreach ($tokens as $token) {

                $this->sendExpoNotification(
                    $token,
                    '⏰ Time Out Reminder',
                    'Your shift has ended. Please time out now.'
                );
            }
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPO PUSH
    |--------------------------------------------------------------------------
    */

    private function sendExpoNotification(
        $token,
        $title,
        $body
    ) {

        $data = [
            'to' => $token,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,

            /*
            |--------------------------------------------------------------------------
            | HIGH PRIORITY
            |--------------------------------------------------------------------------
            */

            'priority' => 'high',

            /*
            |--------------------------------------------------------------------------
            | ANDROID VIBRATION
            |--------------------------------------------------------------------------
            */

            'channelId' => 'dtr-alerts',

            'data' => [
                'type' => 'timeout_reminder'
            ]
        ];

        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            'https://exp.host/--/api/v2/push/send'
        );

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ]
        );

        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($data)
        );

        curl_exec($ch);

        curl_close($ch);
    }
}