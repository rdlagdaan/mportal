<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniversityOffice extends Model
{
    use HasFactory;

    protected $table = 'mobile.university_offices';

    protected $fillable = [
        'office_code',
        'office'
    ];

    /**
     * A university office can have many current units (employees assigned).
     */
    public function currentUnits()
    {
        return $this->hasMany(UniversityCurrentUnit::class, 'office_id', 'id');
    }

    public function employees()
{
    return $this->hasMany(EmployeeProfile::class, 'college_office', 'office_code');
}

}
