<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrScheduleDefinition extends Model
{
    protected $table = 'hris.hr_schedule_definitions';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'code',
        'name',
        'schedule_mode',
        'default_modality',
        'is_flexible',
        'required_hours',
        'core_time_start',
        'core_time_end',
        'remarks',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_flexible' => 'boolean',
        'required_hours' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function days()
    {
        return $this->hasMany(HrScheduleDefinitionDay::class, 'schedule_definition_id');
    }
}