<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeWeeklyScheduleRequestDay extends Model
{
    protected $table = 'hris.hr_employee_weekly_schedule_request_days';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'request_id',
        'day_of_week',
        'is_workday',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'modality',
        'required_hours',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_workday' => 'boolean',
        'required_hours' => 'decimal:2',
    ];

    public function request()
    {
        return $this->belongsTo(HrEmployeeWeeklyScheduleRequest::class, 'request_id');
    }
}