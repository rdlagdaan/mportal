<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrCollege extends Model
{
    protected $table = 'hris.hr_colleges';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'code',
        'name',
        'is_active',
        'created_at',
        'updated_at',
    ];
}