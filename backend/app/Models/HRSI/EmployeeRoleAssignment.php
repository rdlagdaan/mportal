<?php

namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRoleAssignment extends Model
{
    protected $table = 'employee_role_assignments';
    public $timestamps = false;

    protected $fillable = ['employee_id','role_id','org_unit_id','valid_during'];

    public function employee(){ return $this->belongsTo(Employee::class); }
    public function role(){ return $this->belongsTo(\App\Models\HRSI\Lookups\EmployeeRole::class, 'role_id'); }
    public function orgUnit(){ return $this->belongsTo(OrgUnit::class, 'org_unit_id'); }

    public function scopeActive(Builder $q): Builder {
        return $q->whereRaw('(upper_inf(valid_during) OR current_date <@ valid_during)');
    }

    public function getValidFromAttribute(): ?string {
        $r = $this->attributes['valid_during'] ?? null;
        if (!$r) return null; $r = trim($r,'[]()'); return explode(',',$r)[0] ?? null;
    }
    public function getValidToAttribute(): ?string {
        $r = $this->attributes['valid_during'] ?? null;
        if (!$r) return null; $r = trim($r,'[]()'); $to = explode(',',$r)[1] ?? null; return $to === '' ? null : $to;
    }
}
