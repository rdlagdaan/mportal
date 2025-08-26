<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AssetType extends Model
{
    protected $table = 'assets.asset_types';

    protected $fillable = [
        'company_id','cat_code','type_code','type_name',
        'life_months_override','depr_method_override','residual_rate_override',
        'is_active','sort_order','workstation_id','user_id',
    ];

    protected $casts = [
        'life_months_override'   => 'integer',
        'residual_rate_override' => 'float',
        'is_active'              => 'boolean',
        'company_id'             => 'integer',
        // cat_code is varchar; no cast needed
    ];

    /* ---------------- Scopes ---------------- */

    public function scopeCompany(Builder $q, ?int $companyId): Builder
    {
        return $companyId ? $q->where('company_id', $companyId) : $q;
    }

    /** Filter by category code (varchar) */
    public function scopeForCategoryCode(Builder $q, ?string $catCode): Builder
    {
        $catCode = $catCode ? trim($catCode) : null;
        return $catCode ? $q->where('cat_code', $catCode) : $q;
    }

    /** @deprecated: kept for backward compatibility */
    public function scopeForCategory(Builder $q, $catId): Builder
    {
        // no-op to avoid breaking old calls; prefer ->forCategoryCode()
        return $q;
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
        $t = mb_strtolower(trim($term));

        return $q->where(fn ($w) => $w
            ->whereRaw('lower(type_code) like ?', ["%{$t}%"])
            ->orWhereRaw('lower(type_name) like ?', ["%{$t}%"])
        );
    }

    public function scopeSorted(Builder $q, string $order = 'type_code'): Builder
    {
        return match ($order) {
            'name'   => $q->orderBy('type_name'),
            'recent' => $q->orderByDesc('updated_at'),
            'sort'   => $q->orderBy('sort_order')->orderBy('type_code'),
            default  => $q->orderBy('type_code'),
        };
    }
}
