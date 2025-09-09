<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Model;

class CityMunicipality extends Model
{
    protected $table = 'reference.cities_municipalities';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['citymunicipality','cmtype','province_id'];

    protected $casts = [
        'province_id' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
}
