<?php

namespace App\Models\HRSI\LeaveManagement;

use Illuminate\Database\Eloquent\Model;

class EmployeeRole extends Model
{
    public $timestamps = false;
    protected $fillable = ['id','code','name'];
}
