<?php

namespace App\Models\HRSI\LeaveManagement;

use Illuminate\Database\Eloquent\Model;

class EmploymentClass extends Model
{
    protected $table = 'employment_classes';
    protected $fillable = ['code','name'];
}
