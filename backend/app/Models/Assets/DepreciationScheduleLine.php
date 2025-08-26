<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;

class DepreciationScheduleLine extends Model
{
    protected $table = 'depreciation_schedule_lines';
    protected $guarded = [];

    protected $casts = [
        'period' => 'integer',
        'period_start' => 'date',
        'units' => 'float',
        'depreciation' => 'float',
        'accumulated' => 'float',
        'net_book_value' => 'float',
    ];

    public function schedule()
    {
        return $this->belongsTo(DepreciationSchedule::class, 'schedule_id');
    }
}
