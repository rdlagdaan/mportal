<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'assets.asset_details';
    protected $guarded = []; // tighten with $fillable in production
    public $timestamps = true;
    
    protected $casts = [
        'life_months'      => 'integer',
        'residual_rate'    => 'float',
        'quantity'         => 'float',
        'is_serialized'    => 'boolean',
        'purchase_date'    => 'date',
        'in_service_date'  => 'date',
        'warranty_expires' => 'date',
        'vat_inclusive'    => 'boolean',
        'vat_rate'         => 'float',
        'gross_amount'     => 'float',
        'include_in_audits'=> 'boolean',
        'last_audited'     => 'date',
    ];

    public function type()
    {
        return $this->belongsTo(AssetType::class, 'type_code', 'type_code');
    }

    public function supplier()
    {
        return $this->belongsTo(Vendor::class, 'supplier_id');
    }

    public function parent()
    {
        return $this->belongsTo(Asset::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Asset::class, 'parent_id');
    }

    public function serviceLogs()
    {
        return $this->hasMany(AssetServiceLog::class, 'asset_id');
    }
}
