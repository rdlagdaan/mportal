<?php

namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgUnit extends Model
{
    protected $table = 'org_units';
    public $timestamps = false;
    protected $fillable = ['code','name','type','parent_id'];

    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function parent(){ return $this->belongsTo(self::class, 'parent_id'); }
}
