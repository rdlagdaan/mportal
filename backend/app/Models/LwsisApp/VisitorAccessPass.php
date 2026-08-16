<?php

namespace App\Models\LwsisApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VisitorAccessPass extends Model
{
    protected $table = 'mobile.visitor_access_passes';

    protected $fillable = [
        'uuid',
        'visitor_visit_id',
        'token_hash',
        'valid_from',
        'valid_until',
        'status',
        'max_entries',
        'issued_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'max_entries' => 'integer',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (VisitorAccessPass $pass) {
            if (empty($pass->uuid)) {
                $pass->uuid = (string) Str::uuid();
            }
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(
            VisitorVisit::class,
            'visitor_visit_id'
        );
    }
}