<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Assets\AssetClass;
use App\Models\Assets\AssetCategory;

use Illuminate\Support\Facades\Log;  // ← add

class LookupController extends Controller
{
    public function assetTypes(Request $r)
    {
        $q = strtolower((string)$r->query('q',''));
        $cid = $r->user()->company_id ?? 1;

        $rows = DB::table('assets.asset_types')->where('company_id',$cid)
            ->when($q!=='' , fn($w)=> $w
                ->whereRaw('lower(type_name) like ?', ["%$q%"])
                ->orWhereRaw('lower(type_code) like ?', ["%$q%"]))
            ->orderBy('type_name')
            ->limit(50)
            ->get();

        return response()->json($rows);
    }

    public function vendors(Request $r)
    {
        $q = strtolower((string)$r->query('q',''));
        $cid = $r->user()->company_id ?? 1;

        $rows = DB::table('vendors')->where('company_id',$cid)
            ->when($q!=='' , fn($w)=> $w
                ->whereRaw('lower(vendor_name) like ?', ["%$q%"])
                ->orWhereRaw('lower(vendor_code) like ?', ["%$q%"]))
            ->orderBy('vendor_name')
            ->limit(50)
            ->get();

        return response()->json($rows);
    }

public function classes(Request $r)
{
    // BASELINE: no company filter, no is_active, no model scopes.
    $rows = DB::table('assets.asset_classes')
        ->selectRaw('class_code as value, class_name as label')
        ->orderBy('class_code')
        ->limit(200)
        ->get();

    Log::info('LOOKUP classes baseline', [
        'count' => $rows->count(),
    ]);

    return response()->json($rows);
}

    public function categories(\Illuminate\Http\Request $r)
    {
        $companyId = (int)($r->user()->company_id ?? $r->input('company_id'));
        $classCode = trim((string)$r->input('class_code',''));
        $q = trim((string)$r->input('q',''));

        return response()->json(
            \App\Models\Assets\AssetCategory::query()
                ->company($companyId)
                ->where('is_active', true)
                ->when($classCode !== '', fn($qb)=>$qb->where('class_code',$classCode))
                ->when($q !== '', fn($qb)=>$qb->where(fn($w)=>$w
                    ->where('cat_code','ILIKE',"%{$q}%")
                    ->orWhere('cat_name','ILIKE',"%{$q}%")))
                ->orderBy('cat_code')
                ->get(['cat_code as value','cat_name as label','class_code'])
        );
    }


public function depreciationTypes(Request $r)
{
    $q = trim((string)$r->query('q', ''));

    // qualify schema if needed: assets.asset_depreciation_types
    $rows = \DB::table('assets.asset_depreciation_types')
        ->where('is_active', true)
        ->when($q !== '', fn($w) =>
            $w->where(fn($s) => $s
                ->where('code',  'ILIKE', "%{$q}%")
                ->orWhere('label','ILIKE', "%{$q}%")
            )
        )
        ->orderBy('sort_order')->orderBy('code')
        ->limit(200)
        ->get();

    return response()->json($rows->map(fn($r) => [
        'id'             => $r->code,   // 👈 add this
        'value'          => $r->code,
        'label'          => $r->label,
        'is_depreciable' => (bool)$r->is_depreciable,
    ]));
}


    /**
     * Asset Types filtered by Category (cat_code) for cascading combo.
     * Does NOT replace your existing assetTypes(); this is new.
     * Returns shape: [{ value, label, cat_code }]
     */
public function typesByCat(Request $r)
{
    // company guard (same pattern you already use elsewhere)
    $companyId = (int)($r->user()->company_id ?? $r->input('company_id') ?? 0);

    $catCode = trim((string)$r->query('cat_code', ''));
    if ($catCode === '') {
        return response()->json([]); // must have a category
    }

    $q = trim((string)$r->query('q', ''));

    // IMPORTANT: qualify schema AND filter by company_id + is_active
    $rows = DB::table('assets.asset_types')
        ->where('company_id', $companyId)
        ->where('cat_code', $catCode)
        ->where('is_active', true)
        ->when($q !== '', fn($w) =>
            $w->where(function ($s) use ($q) {
                $s->where('type_code', 'ILIKE', "%{$q}%")
                  ->orWhere('type_name', 'ILIKE', "%{$q}%");
            })
        )
        ->orderBy('sort_order')
        ->orderBy('type_code')
        ->limit(100)
        ->get(['id', 'type_code', 'type_name']);

    // Shape to what <SearchableCombo> expects
    return response()->json($rows->map(fn($r) => [
        'id'    => $r->id,
        'value' => $r->type_code,
        'label' => trim($r->type_code . ' — ' . ($r->type_name ?? '')),
    ]));
}






}
