<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;

class DailyTimeRecord extends Model
{
    protected $table = 'mobile.daily_time_records';

    protected $fillable = [
        'user_id',
        'work_date',
        'time_in',
        'time_out',
        'time_in_lat',
        'time_in_lng',
        'time_out_lat',
        'time_out_lng',
        'attendance_status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];
}