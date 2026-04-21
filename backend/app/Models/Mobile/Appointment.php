<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'mobile.appointments';

    protected $fillable = [
        'student_user_id',
        'employee_user_id',
        'purpose',
        'appointment_date',
        'status',
        'appointment_title',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Users::class, 'student_user_id');
    }

    public function employee()
    {
        return $this->belongsTo(Users::class, 'employee_user_id');
    }
}