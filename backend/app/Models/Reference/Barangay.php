<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $table = 'reference.barangays';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['barangay','cmid'];

    protected $casts = [
        'cmid'       => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
