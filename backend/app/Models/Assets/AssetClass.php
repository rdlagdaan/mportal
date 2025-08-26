<?php

namespace App\Models\Assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AssetClass extends Model
{
    protected $table = 'assets.asset_classes';

    protected $fillable = [
        'company_id','class_code','class_name',
        'default_life_months','default_depr_method','residual_rate',
        'is_active','sort_order','workstation_id','user_id',
    ];

    protected $casts = [
        'default_life_months' => 'integer',
        'residual_rate' => 'float',
        'is_active' => 'boolean',
        'company_id' => 'integer',
    ];

    public function scopeCompany(Builder $q, ?int $companyId): Builder {
        return $companyId ? $q->where('company_id', $companyId) : $q;
    }
    public function scopeSearch(Builder $q, ?string $term): Builder {
        if (!$term) return $q;
        $t = mb_strtolower(trim($term));
        return $q->where(fn($w)=>$w
            ->whereRaw('lower(class_code) like ?', ["%{$t}%"])
            ->orWhereRaw('lower(class_name) like ?', ["%{$t}%"])
        );
    }
    public function scopeSorted(Builder $q, string $order='class_code'): Builder {
        return match ($order) {
            'name' => $q->orderBy('class_name'),
            'recent' => $q->orderByDesc('updated_at'),
            'sort' => $q->orderBy('sort_order')->orderBy('class_code'),
            default => $q->orderBy('class_code'),
        };
    }

    public function scopeActive($q){ return $q->where('is_active', true); }


}
