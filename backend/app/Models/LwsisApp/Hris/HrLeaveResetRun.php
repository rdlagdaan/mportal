<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrLeaveResetRun extends Model
{
    protected $table = 'hris.hr_leave_reset_runs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'leave_year',
        'category',
        'tier_scope',
        'as_of_date',
        'status',
        'employees_affected',
        'remarks',
        'run_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'leave_year' => 'integer',
        'employees_affected' => 'integer',
        'as_of_date' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function runBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'run_by');
    }
}