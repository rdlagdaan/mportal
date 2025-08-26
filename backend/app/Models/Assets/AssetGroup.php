<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssetGroup extends Model
{
    // If you decide to use a schema: protected $table = 'fixedassets.asset_groups';
    protected $table = 'asset_groups';

    protected $fillable = [
        'group_code',
        'group_name',
        'name',                 // ✅ actual asset name / canonical display
        'default_life_months',
        'default_depr_method',
        'residual_rate',
        'is_active',
        'sort_order',
        'company_id',
        'workstation_id',
        'user_id',
    ];

    protected $casts = [
        'default_life_months' => 'integer',
        'residual_rate'       => 'float',
        'is_active'           => 'boolean',
        'company_id'          => 'integer',
        'user_id'             => 'integer',
    ];

    /** Tenant scope */
    public function scopeCompany(Builder $q, ?int $companyId): Builder
    {
        return $companyId ? $q->where('company_id', $companyId) : $q;
    }

    /** Case-insensitive search across common fields */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
        $t = mb_strtolower(trim($term));
        return $q->where(function($w) use ($t) {
            $w->whereRaw('lower(group_code)  like ?', ["%{$t}%"])
              ->orWhereRaw('lower(group_name) like ?', ["%{$t}%"])
              ->orWhereRaw('lower(name)       like ?', ["%{$t}%"]);
        });
    }

    /** Optional sort helper */
    public function scopeSorted(Builder $q, string $order = 'group_code'): Builder
    {
        return match ($order) {
            'name'       => $q->orderBy('name'),
            'group_name' => $q->orderBy('group_name'),
            'recent'     => $q->orderByDesc('updated_at'),
            'sort'       => $q->orderBy('sort_order')->orderBy('group_code'),
            default      => $q->orderBy('group_code'),
        };
    }
}
