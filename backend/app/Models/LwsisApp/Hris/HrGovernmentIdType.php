<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrGovernmentIdType extends Model
{
    protected $table = 'hris.hr_government_id_types';

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

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employeeGovernmentIds()
    {
        return $this->hasMany(HrEmployeeGovernmentId::class, 'gov_id_type_id');
    }
}