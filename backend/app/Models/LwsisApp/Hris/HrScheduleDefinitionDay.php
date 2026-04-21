<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrScheduleDefinitionDay extends Model
{
    protected $table = 'hris.hr_schedule_definition_days';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'schedule_definition_id',
        'day_of_week',
        'is_workday',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'modality',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_workday' => 'boolean',
    ];

    public function scheduleDefinition()
    {
        return $this->belongsTo(HrScheduleDefinition::class, 'schedule_definition_id');
    }
}