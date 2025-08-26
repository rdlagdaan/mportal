<?php

namespace App\Models\HRSI\LeaveManagement;

use Illuminate\Database\Eloquent\Model;

class OrgUnit extends Model
{
    protected $fillable = ['code','name','parent_id','is_vp_office','reports_to_president'];

    public function parent()  { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(){ return $this->hasMany(self::class, 'parent_id'); }
}
