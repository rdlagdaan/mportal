<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;
use App\Models\LwsisApp\Hris\EmployeeWeeklyScheduleRequest;

class EmployeeWeeklyScheduleRequestDetail extends Model
{
   protected $table = 'hris.hr_employee_weekly_schedule_request_days';

    protected $guarded = [];

    public function request()
    {
        return $this->belongsTo(
            EmployeeWeeklyScheduleRequest::class,
            'request_id'
        );
    }
}