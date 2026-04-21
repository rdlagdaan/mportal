<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'course_program_id';

    protected $fillable = [
        'course_program_name',
        'course_program_description'
    ];

    public function student()
    {
        return $this->hasMany(Student::class, 'college_program', 'course_program_name');
    }


}
