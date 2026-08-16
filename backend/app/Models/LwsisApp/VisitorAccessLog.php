<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VisitorAccessLog extends Model
{
    protected $table = 'kiosk.visitor_access_logs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'visitor_id',
        'visitor_visit_id',
        'visitor_access_pass_id',
        'event_type',
        'result',
        'denial_reason',
        'scanned_at',
        'created_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (VisitorAccessLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }

            if (empty($log->scanned_at)) {
                $log->scanned_at = now();
            }

            if (empty($log->created_at)) {
                $log->created_at = now();
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

    public function visit(): BelongsTo
    {
        return $this->belongsTo(
            VisitorVisit::class,
            'visitor_visit_id'
        );
    }

    public function accessPass(): BelongsTo
    {
        return $this->belongsTo(
            VisitorAccessPass::class,
            'visitor_access_pass_id'
        );
    }
}