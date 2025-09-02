<?php
namespace App\Models\mdo;

use Illuminate\Database\Eloquent\Model;

class Encounter extends Model
{
    protected $table = 'mdo.encounters';
    public $timestamps = true;

    protected $fillable = [
        'company_id','person_id','provider_id',
        'encounter_type','status','chief_complaint','started_at','ended_at',
        'created_by','updated_by'
    ];

    public function person(){ return $this->belongsTo(Person::class, 'person_id'); }
    public function provider(){ return $this->belongsTo(Provider::class, 'provider_id'); }
    public function vitals(){ return $this->hasMany(Vital::class, 'encounter_id'); }
}
