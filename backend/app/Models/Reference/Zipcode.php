<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Model;

class Zipcode extends Model
{
    protected $table = 'reference.zipcodes';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['zipcode','barangay_id','cmid','province_id'];

    protected $casts = [
        'barangay_id' => 'integer',
        'cmid'        => 'integer',
        'province_id' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
}
