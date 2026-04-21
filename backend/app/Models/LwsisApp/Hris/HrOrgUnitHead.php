<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrOrgUnitHead extends Model
{
    protected $table = 'hris.hr_org_unit_heads';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'org_unit_id',
        'head_employee_id',
        'effective_from',
        'effective_to',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function orgUnit()
    {
        return $this->belongsTo(HrOrgUnit::class, 'org_unit_id');
    }

    public function head()
    {
        return $this->belongsTo(HrEmployee::class, 'head_employee_id');
    }
}