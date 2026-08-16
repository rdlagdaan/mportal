<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VisitorVisit extends Model
{
    protected $table = 'mobile.visitor_visits';

    protected $fillable = [
        'uuid',
        'visitor_id',
        'purpose',
        'destination',
        'person_to_visit',
        'visit_date',
        'expected_time_from',
        'expected_time_to',
        'status',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'visit_date' => 'date:Y-m-d',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (VisitorVisit $visit) {
            if (empty($visit->uuid)) {
                $visit->uuid = (string) Str::uuid();
            }
        });
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(
            Visitor::class,
            'visitor_id'
        );
    }

    public function accessPasses(): HasMany
    {
        return $this->hasMany(
            VisitorAccessPass::class,
            'visitor_visit_id'
        );
    }
}