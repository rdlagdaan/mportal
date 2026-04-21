<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    // Point to schema.table
    protected $table = 'mobile.student_profiles';

    // Primary key (if not default `id`)
    protected $primaryKey = 'id';

    // If the table uses auto-increment
    public $incrementing = true;

    // If your PK is an integer
    protected $keyType = 'int';

    // Enable timestamps (since your table has created_at, updated_at)
    public $timestamps = true;

    // Mass assignable fields
    protected $fillable = [
        'student_number',
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
    ];
}