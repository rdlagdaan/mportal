<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;

class DepreciationSchedule extends Model
{
    protected $table = 'depreciation_schedules';
    protected $guarded = [];

    protected $casts = [
        'cost' => 'float',
        'residual_rate' => 'float',
        'residual_value' => 'float',
        'life_months' => 'integer',
        'start_date' => 'date',
        'options' => 'array',
        'total_depreciation' => 'float',
        'ending_nbv' => 'float',
    ];

    public function lines()
    {
        return $this->hasMany(DepreciationScheduleLine::class, 'schedule_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
