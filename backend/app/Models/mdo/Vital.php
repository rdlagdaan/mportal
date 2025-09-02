<?php
namespace App\Models\mdo;

use Illuminate\Database\Eloquent\Model;

class Vital extends Model
{
    protected $table = 'mdo.vitals';
    public $timestamps = true;

    protected $fillable = [
        'encounter_id','systolic','diastolic','hr','rr',
        'temp_c','spo2','height_cm','weight_kg','taken_at'
    ];

    public function encounter(){ return $this->belongsTo(Encounter::class, 'encounter_id'); }
}
