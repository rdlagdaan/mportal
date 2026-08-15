<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployee extends Model
{
    protected $table = 'hris.hr_employees';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'org_unit_id',
        'employee_no',
        'biometrics_no',
        'prefix_name',
        'last_name',
        'first_name',
        'middle_name',
        'maiden_name',
        'suffix_name',
        'gender_id',
        'civil_status_id',
        'birth_date',
        'birth_place',
        'citizenship_id',
        'religion_id',
        'ethnicity',
        'height_cm',
        'weight_kg',
        'blood_type',
        'email_personal',
        'email_work',
        'mobile_no',
        'telephone_no',
        'photo_path',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Org
    public function orgUnit()
    {
        return $this->belongsTo(HrOrgUnit::class, 'org_unit_id');
    }

    public function activeOrgMembership()
{
    return $this->hasOne(
        HrOrgUnitMembership::class,
        'employee_id'
    )
    ->where('is_active', true)
    ->where('is_primary', true)
    ->latest('effective_from');
}

    // Core HR relations
    public function addresses()
    {
        return $this->hasMany(HrEmployeeAddress::class, 'employee_id');
    }

    public function familyMembers()
    {
        return $this->hasMany(HrEmployeeFamilyMember::class, 'employee_id');
    }

    public function education()
    {
        return $this->hasMany(HrEmployeeEducation::class, 'employee_id');
    }

    public function governmentIds()
    {
        return $this->hasMany(HrEmployeeGovernmentId::class, 'employee_id');
    }

    public function functions()
    {
        return $this->hasMany(HrEmployeeFunction::class, 'employee_id');
    }

    public function supervisors()
    {
        return $this->hasMany(HrEmployeeSupervisor::class, 'employee_id');
    }

    public function subordinates()
    {
        return $this->hasMany(HrEmployeeSupervisor::class, 'supervisor_employee_id');
    }

    public function teachingColleges()
    {
        return $this->hasMany(HrEmployeeTeachingCollege::class, 'employee_id');
    }

    public function appointments()
    {
        return $this->hasMany(HrEmployeeAppointment::class, 'employee_id');
    }

    // Schedule
    public function scheduleBatches()
    {
        return $this->hasMany(HrEmployeeScheduleBatch::class, 'employee_id');
    }

    public function scheduleDetails()
    {
        return $this->hasMany(HrEmployeeScheduleDetail::class, 'employee_id');
    }

    public function weeklyScheduleRequests()
    {
        return $this->hasMany(HrEmployeeWeeklyScheduleRequest::class, 'employee_id');
    }

    // Change Requests
    public function changeBatches()
    {
        return $this->hasMany(HrEmployeeChangeBatch::class, 'employee_id');
    }

    public function changeRequests()
    {
        return $this->hasMany(HrEmployeeChangeRequest::class, 'employee_id');
    }
}