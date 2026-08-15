<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrOrgUnitMembership extends Model
{
    protected $table = 'hris.hr_org_unit_memberships';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'org_unit_id',
        'position_class_id',
        'job_title_id',
        'is_primary',
        'effective_from',
        'effective_to',
        'is_active',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function positionClass()
    {
        return $this->belongsTo(HrPositionClass::class, 'position_class_id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(HrJobTitle::class, 'job_title_id');
    }
    public function orgUnit()
{
    return $this->belongsTo(
        HrOrgUnit::class,
        'org_unit_id'
    );
}
}