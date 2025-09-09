<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'reference.provinces';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['province_name','region_id'];

    protected $casts = [
        'region_id'  => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
