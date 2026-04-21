<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Mobile\UniversityCurrentUnit;
use App\Models\Mobile\UniversityOffice;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $table = 'mobile.employee_profiles';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'employee_number',
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'address',
        'barangay',
        'city_municipality',
        'province',
        'region',
        'zip_code',
        'college_office',
        'college_office_desc',
        'program',
        'program_desc',
        'employee_rank',
        'employee_type',
        'employee_position',
        'active',
    ];

    protected $casts = [
        'employee_number' => 'string',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function currentUnit()
    {
        return $this->hasOne(
            UniversityCurrentUnit::class,
            'employee_id',
            'id'
        );
    }

    public function office()
    {
        return $this->belongsTo(
            UniversityOffice::class,
            'college_office',   // employee_profiles.college_office
            'office_code'       // university_offices.office_code
        );
    }
}
