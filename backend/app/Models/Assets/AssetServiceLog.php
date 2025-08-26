<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;

class AssetServiceLog extends Model
{
    protected $table = 'asset_service_logs';
    protected $guarded = [];

    protected $casts = [
        'service_date' => 'date',
        'next_due_date'=> 'date',
        'parts_cost'   => 'float',
        'labor_cost'   => 'float',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
