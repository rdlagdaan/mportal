<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmploymentRecord extends Model
{
    protected $table = 'hris.hr_employment_records';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'employment_status_id',
        'employee_type_id',
        'date_hired',
        'date_end',
        'is_primary',
        'is_active',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'date_hired' => 'date',
        'date_end' => 'date',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function employmentStatus()
    {
        return $this->belongsTo(HrEmploymentStatus::class, 'employment_status_id');
    }

    public function employeeType()
    {
        return $this->belongsTo(HrEmployeeType::class, 'employee_type_id');
    }
}