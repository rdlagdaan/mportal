<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeAppointment extends Model
{
    protected $table = 'hris.hr_employee_appointments';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'org_unit_id',
        'job_title_id',
        'position_class_id',
        'start_date',
        'end_date',
        'is_primary',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // FK: employee_id → hr_employees
    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    // FK: org_unit_id → hr_org_units
    public function orgUnit()
    {
        return $this->belongsTo(HrOrgUnit::class, 'org_unit_id');
    }

    // FK: job_title_id → hr_job_titles
    public function jobTitle()
    {
        return $this->belongsTo(HrJobTitle::class, 'job_title_id');
    }

    // FK: position_class_id → hr_position_classes
    public function positionClass()
    {
        return $this->belongsTo(HrPositionClass::class, 'position_class_id');
    }
}