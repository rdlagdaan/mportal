<?php

namespace App\Http\Controllers\LwsisApp\admin;

use App\Http\Controllers\Controller;
use App\Models\Mobile\DailyTimeRecord;
use Illuminate\Http\Request;

class AdminDailyTimeRecordController extends Controller
{
    /**
     * Display DTR records for the selected date.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        $records = DailyTimeRecord::query()
            ->select([
                'mobile.daily_time_records.id',
                'mobile.daily_time_records.user_id',
                'mobile.daily_time_records.work_date',
                'mobile.daily_time_records.time_in',
                'mobile.daily_time_records.time_out',
                'mobile.daily_time_records.time_in_lat',
                'mobile.daily_time_records.time_in_lng',
                'mobile.daily_time_records.time_out_lat',
                'mobile.daily_time_records.time_out_lng',
                'mobile.daily_time_records.attendance_status',

                'users.name as full_name',
                'employees.employee_no',
            ])
            ->join(
                'iam.users as users',
                'users.id',
                '=',
                'mobile.daily_time_records.user_id'
            )
            ->leftJoin(
                'iam.user_employee_links as links',
                'links.user_id',
                '=',
                'users.id'
            )
            ->leftJoin(
                'hris.hr_employees as employees',
                'employees.id',
                '=',
                'links.employee_id'
            )
            ->whereDate(
                'mobile.daily_time_records.work_date',
                $date
            )
            ->orderByRaw(
                'COALESCE(
                    mobile.daily_time_records.time_out,
                    mobile.daily_time_records.time_in
                ) DESC'
            )
            ->get();

        return response()->json([
            'status' => 'success',
            'date'   => $date,
            'total'  => $records->count(),
            'records'=> $records,
        ]);
    }
}