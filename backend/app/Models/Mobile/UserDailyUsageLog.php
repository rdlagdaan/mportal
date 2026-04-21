<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class UserDailyUsageLog extends Model
{
    protected $table = 'mobile.user_daily_usage_logs';

    protected $fillable = [
        'user_id',
        'date',
        'first_open_at',
        'last_open_at',
    ];

    protected $casts = [
        'date'          => 'date',
        'first_open_at' => 'datetime',
        'last_open_at'  => 'datetime',
    ];

    /**
     * Scope: logs for today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('date', now()->toDateString());
    }

    /**
     * Mark app opened (idempotent)
     */
    public static function markOpen(int $userId): self
    {
        $today = now()->toDateString();
        $now   = now();

        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'date'    => $today,
            ],
            [
                'first_open_at' => self::where('user_id', $userId)
                    ->where('date', $today)
                    ->value('first_open_at') ?? $now,
                'last_open_at'  => $now,
            ]
        );
    }

    /**
     * Relationship (optional)
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\Mobile\Users::class);
    }
}
