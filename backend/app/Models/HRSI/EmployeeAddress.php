<?php

namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EmployeeAddress extends Model
{
    protected $table = 'employee_addresses';
    public $timestamps = true;

    protected $fillable = [
        'employee_id','address_type','address_line','district','barangay',
        'city_municipality','province','region','postal_code','country_code',
        'valid_during','created_at','updated_at'
    ];

    public function employee(){ return $this->belongsTo(Employee::class); }

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
