<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Announcement extends Model
{
    // ⬇️ Use the student schema (keep the exact table name you actually created)
    protected $table = 'student.announcements'; // or: 'student.announcements' if spelled with the extra "e"

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'start_time'   => 'string',     // TIME → string
        'end_time'     => 'string',
        'deadline_at'  => 'datetime',
        'is_published' => 'boolean',
        'links'        => 'array',
        'attachments'  => 'array',
        'metadata'     => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public const ORDER_MAP = [
        'start_date'  => 'start_date',
        'end_date'    => 'end_date',
        'created_at'  => 'created_at',
        'updated_at'  => 'updated_at',
        'title'       => 'title',
        'importance'  => 'importance',
        'deadline_at' => 'deadline_at',
    ];

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)
                 ->where('status', '!=', 'archived');
    }

    public function scopeCompany(Builder $q, ?int $companyId): Builder
    {
        return $companyId ? $q->where('company_id', $companyId) : $q;
    }

    // Safe, ILIKE-only search
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        $t = trim($term);
        return $q->where(function (Builder $w) use ($t) {
            $w->where('title', 'ILIKE', "%{$t}%")
              ->orWhere('details', 'ILIKE', "%{$t}%")
              ->orWhere('announcement_code', 'ILIKE', "%{$t}%")
              ->orWhere('audience', 'ILIKE', "%{$t}%")
              ->orWhere('venue', 'ILIKE', "%{$t}%");
        });
    }
}
