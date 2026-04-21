<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Mobile\College;

class EnrollmentHistory extends Model
{
    use HasFactory;

    // Full schema.table reference
    protected $table = 'mobile.enrollment_history';

    // Primary key
    protected $primaryKey = 'id';

    // If id is auto-incrementing
    public $incrementing = true;

    // Primary key type
    protected $keyType = 'int';

    // Enable timestamps (since you have created_at, updated_at)
    public $timestamps = true;

    // Mass assignable fields
    protected $fillable = [
        'student_number',
        'sem',
        'sy',
        'college_code',
        'college_description',
        'course_code',
        'course_description',
        'year_level',
        'status_code',
        'status_description',
        'type_code',
        'type_description',
    ];

    public function college()
    {
        return $this->belongsTo(College::class, 'college_code', 'college_code');
    }
}