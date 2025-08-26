<?php
namespace App\Models\HRSI\Lookups;

use Illuminate\Database\Eloquent\Model;

class EmployeeRole extends Model
{
    protected $table = 'employee_rolles'; // ← NOTE: renamed table
    public $timestamps = false;
    protected $fillable = ['code','name'];
}
