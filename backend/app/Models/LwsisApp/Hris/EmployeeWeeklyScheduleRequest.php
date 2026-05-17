<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;
use App\Models\LwsisApp\Hris\EmployeeWeeklyScheduleRequestDetail;

class EmployeeWeeklyScheduleRequest extends Model
{
    protected $table = 'hris.hr_employee_weekly_schedule_requests';

    protected $guarded = [];

    public function details()
    {
        return $this->hasMany(
            EmployeeWeeklyScheduleRequestDetail::class,
            'request_id'
        );
    }
}