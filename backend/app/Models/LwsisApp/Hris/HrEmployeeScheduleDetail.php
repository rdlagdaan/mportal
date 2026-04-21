<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeScheduleDetail extends Model
{
    protected $table = 'hris.hr_employee_schedule_details';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'batch_id',
        'employee_id',
        'org_unit_id',
        'schedule_date',
        'schedule_definition_id',
        'source_type',
        'day_of_week',
        'is_workday',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'modality',
        'required_hours',
        'is_override',
        'status',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'time_in' => 'datetime:H:i:s',
        'time_out' => 'datetime:H:i:s',
        'break_start' => 'datetime:H:i:s',
        'break_end' => 'datetime:H:i:s',
        'required_hours' => 'decimal:2',
        'is_workday' => 'boolean',
        'is_override' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function batch()
    {
        return $this->belongsTo(HrEmployeeScheduleBatch::class, 'batch_id');
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function orgUnit()
    {
        return $this->belongsTo(HrOrgUnit::class, 'org_unit_id');
    }

    public function scheduleDefinition()
    {
        return $this->belongsTo(HrScheduleDefinition::class, 'schedule_definition_id');
    }
}