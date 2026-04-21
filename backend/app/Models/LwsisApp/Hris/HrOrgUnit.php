<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrOrgUnit extends Model
{
    protected $table = 'hris.hr_org_units';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'college_id',
        'code',
        'name',
        'unit_type',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function college()
    {
        return $this->belongsTo(HrCollege::class, 'college_id');
    }

    public function members()
    {
        return $this->hasMany(HrOrgUnitMembership::class, 'org_unit_id');
    }

    public function heads()
    {
        return $this->hasMany(HrOrgUnitHead::class, 'org_unit_id');
    }
}