<?php
namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    protected $table = 'employee_education';
    public $timestamps = false;
    protected $fillable = ['employee_id','level_id','school_name','school_location','date_from','date_to','is_completed','course','honors'];
    protected $casts = ['is_completed'=>'boolean','date_from'=>'date','date_to'=>'date'];
    public function level(){ return $this->belongsTo(\App\Models\HRSI\Lookups\EducationLevel::class,'level_id'); }
}
