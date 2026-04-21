<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Notice extends Model
{
    // Your table is in the student schema
    protected $table = 'student.notices';

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'start_time'   => 'string',   // TIME → string
        'end_time'     => 'string',
        'due_at'       => 'datetime',
        'grace_until'  => 'datetime',
        'is_published' => 'boolean',
        'links'        => 'array',
        'attachments'  => 'array',
        'metadata'     => 'array',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public const ORDER_MAP = [
        'due_at'       => 'due_at',
        'grace_until'  => 'grace_until',
        'start_date'   => 'start_date',
        'end_date'     => 'end_date',
        'created_at'   => 'created_at',
        'updated_at'   => 'updated_at',
        'title'        => 'title',
        'importance'   => 'importance',
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
        if (!$term) return $q;
        $t = trim($term);

        return $q->where(function (Builder $w) use ($t) {
            $w->where('title', 'ILIKE', "%{$t}%")
              ->orWhere('details', 'ILIKE', "%{$t}%")
              ->orWhere('notice_code', 'ILIKE', "%{$t}%")
              ->orWhere('category', 'ILIKE', "%{$t}%")
              ->orWhere('audience', 'ILIKE', "%{$t}%")
              ->orWhere('target_student_number', 'ILIKE', "%{$t}%")
              ->orWhere('venue', 'ILIKE', "%{$t}%");
        });
    }
}
