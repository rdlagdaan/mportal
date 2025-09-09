<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'reference.regions';        // schema-qualified
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'region_code','region_name','region_details','territory',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
