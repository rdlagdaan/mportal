<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AssetCategory extends Model
{
    protected $table = 'assets.asset_categories';

    protected $fillable = [
        'company_id',
        'class_code',   // ← was class_id
        'cat_code',
        'cat_name',
        'is_active',
        'sort_order',
        'workstation_id',
        'user_id',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'is_active'  => 'boolean',
        // class_code is a string/varchar
    ];

    /** Company filter */
    public function scopeCompany(Builder $q, ?int $companyId): Builder
    {
        return $companyId ? $q->where('company_id', $companyId) : $q;
    }

    /**
     * Filter by class_code (new way).
     */
    public function scopeForClassCode(Builder $q, ?string $classCode): Builder
    {
        return $classCode ? $q->where('class_code', $classCode) : $q;
    }

    /**
     * (Optional) Back‑compat stub if anything still calls ->forClass($id).
     * You can safely remove this once old calls are gone.
     */
    public function scopeForClass(Builder $q, $unused = null): Builder
    {
        return $q; // no-op now that class_id is removed
    }

    /** Search by code/name */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
        $t = mb_strtolower(trim($term));

        return $q->where(function ($w) use ($t) {
            $w->whereRaw('lower(cat_code) like ?', ["%{$t}%"])
              ->orWhereRaw('lower(cat_name) like ?', ["%{$t}%"]);
        });
    }

    /** Sorting */
    public function scopeSorted(Builder $q, string $order = 'cat_code'): Builder
    {
        return match ($order) {
            'name'   => $q->orderBy('cat_name'),
            'recent' => $q->orderByDesc('updated_at'),
            'sort'   => $q->orderBy('sort_order')->orderBy('cat_code'),
            default  => $q->orderBy('cat_code'),
        };
    }

    /**
     * Optional: relation to AssetClass via class_code (owner key).
     * Make sure App\Models\Assets\AssetClass has a unique `class_code`.
     */
    public function assetClass()
    {
        return $this->belongsTo(AssetClass::class, 'class_code', 'class_code');
    }

    public function scopeActive($q){ return $q->where('is_active', true); }

}
