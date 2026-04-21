<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    // Schema-qualified table
    protected $table = 'mobile.student_grades';

    // Primary key
    protected $primaryKey = 'id';

    // Auto-increment (true by default for integer PKs)
    public $incrementing = true;

    // If your PK is an int (default is int, change to string if otherwise)
    protected $keyType = 'int';

    // Mass assignable fields
    protected $fillable = [
        'student_number',
        'parent_section',
        'subject_code',
        'sem',
        'sy',
        'grade_status',
        'grade',
        'faculty_number',
        'subject_description'
    ];

    // Casts
    protected $casts = [
        'grade'      => 'decimal:2',  // keep grade numeric with 2 decimal places
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}