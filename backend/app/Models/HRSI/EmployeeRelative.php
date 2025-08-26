<?php
namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;

class EmployeeRelative extends Model
{
    protected $table = 'employee_relatives';
    public $timestamps = false;
    protected $fillable = ['employee_id','type_id','full_name','birth_date','occupation','employer','address','contact_no','is_dependent'];
    protected $casts = ['is_dependent'=>'boolean','birth_date'=>'date'];
    public function type(){ return $this->belongsTo(\App\Models\HRSI\Lookups\RelativeType::class,'type_id'); }
}
