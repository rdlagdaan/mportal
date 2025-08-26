<?php

namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EmployeeContact extends Model
{
    protected $table = 'employee_contacts';
    public $timestamps = true;

    protected $fillable = [
        'employee_id','type','value','is_primary','valid_during',
        'created_at','updated_at'
    ];

    protected $casts = ['is_primary'=>'boolean'];

    public function employee(){ return $this->belongsTo(Employee::class); }

    /** Contacts whose daterange includes today */
    public function scopeActive(Builder $q): Builder {
        return $q->whereRaw('(upper_inf(valid_during) OR current_date <@ valid_during)');
    }

    public function getValidFromAttribute(): ?string {
        if (!isset($this->attributes['valid_during'])) return null;
        $r = trim($this->attributes['valid_during'], '[]()');
        return explode(',', $r)[0] ?? null;
    }
    public function getValidToAttribute(): ?string {
        if (!isset($this->attributes['valid_during'])) return null;
        $r = trim($this->attributes['valid_during'], '[]()');
        $to = explode(',', $r)[1] ?? null;
        return $to === '' ? null : $to;
    }
}
