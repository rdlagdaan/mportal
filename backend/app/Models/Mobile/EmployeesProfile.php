<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeesProfile extends Model
{
    use HasFactory;

    protected $table = 'mobile.employees_profile';  // adjust if your table has a schema (e.g., hr.employees_profile)
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // Change to true if your table uses created_at / updated_at

    protected $fillable = [
        'employee_number',
        'prefix_name',
        'last_name',
        'first_name',
        'middle_name',
        'maiden_name',
        'suffix_name',
        'head',
        'gender',
        'current_department',
        'sub_department',
        'institute',
        'sub_institute',
        'service_department',
        'active',
        'civil_status',
        'birth_date',
        'birth_place',
        'citizenship_id',
        'religion_id',
        'ethnicity',
        'height_feet',
        'height_inches',
        'weight',
        'blood_type',
        'city_street',
        'city_barangay',
        'city_town',
        'city_city',
        'city_country',
        'city_zip',
        'provincial_street',
        'provincial_brgy',
        'provincial_town',
        'provincial_city',
        'provincial_country',
        'provincial_zip',
        'telephone_number',
        'mobile_number',
        'tin',
        'sss',
        'pagibig',
        'spouse_name',
        'spouse_occupation',
        'spouse_employer',
        'spouse_birth_day',
        'father_name',
        'father_birth_day',
        'father_status',
        'father_occupation',
        'father_employer',
        'mother_name',
        'mother_birth_day',
        'mother_status',
        'mother_occupation',
        'mother_employer',
        'parent_address',
        'regular_load',
        'part_time_load',
        'employee_type',
        'employee_category',
        'faculty_status',
        'employee_code',
        'date_hired',
        'email_address',
        'tua_email_address',
        'prc',
        'phil_health',
        'nick_name',
        'date_created',
        'created_by',
        'height',
        'time_stamp',
    ];

    protected $casts = [
        'active' => 'boolean',
        'birth_date' => 'date',
        'spouse_birth_day' => 'date',
        'father_birth_day' => 'date',
        'mother_birth_day' => 'date',
        'date_hired' => 'date',
        'date_created' => 'datetime',
        'time_stamp' => 'datetime',
        'height_feet' => 'integer',
        'height_inches' => 'integer',
        'weight' => 'integer',
    ];
}
