<?php
namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;

class EmployeeGovId extends Model
{
    protected $table = 'employee_government_ids';
    public $timestamps = false;
    protected $fillable = ['employee_id','type_id','id_number','issued_at','expires_at','notes'];
    protected $casts = ['issued_at'=>'date','expires_at'=>'date'];
    public function type(){ return $this->belongsTo(\App\Models\HRSI\Lookups\GovIdType::class,'type_id'); }
}
