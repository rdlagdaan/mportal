<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Assets\AssetCategory;

class AssetTypeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId   = $user->company_id ?? (int) $request->input('company_id');
        $categoryCode = $request->input('cat_code'); // varchar now
        $q           = $request->input('q');
        $order       = $request->input('order', 'type_code');
        $per         = max(1, (int) $request->input('per_page', 10));

        $rows = AssetType::query()
            ->company($companyId)
            ->forCategoryCode($categoryCode)   // <-- scope uses cat_code now
            ->search($q)
            ->sorted($order)
            ->paginate($per);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $data = $request->validate([
            'cat_code' => [
                'required','string','max:25',
                Rule::exists(AssetCategory::class, 'cat_code')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'type_code' => [
                'required','string','max:25',
                Rule::unique(AssetType::class, 'type_code')
                    ->where(fn ($q) => $q->where('company_id', $companyId)
                                        ->where('cat_code', $request->input('cat_code'))),
            ],
            'type_name' => ['required','string','max:150'],
            'life_months_override'   => ['nullable','integer','min:1','max:360'],
            'depr_method_override'   => ['nullable', Rule::in(['straight_line','declining_balance','units_of_production','none'])],
            'residual_rate_override' => ['nullable','numeric','min:0','max:100'],
            'is_active'              => ['sometimes','boolean'],
            'sort_order'             => ['sometimes','integer','min:0','max:100000'],
        ]);

        $m = new AssetType($data);                     // model fillable must include 'cat_code' (not category_id)
        $m->company_id    = $companyId;
        $m->user_id       = $user?->id;
        $m->workstation_id = $request->header('X-Workstation-Id') ?? $request->ip();
        $m->save();

        return response()->json($m, 201);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $m = AssetType::query()->company($companyId)->findOrFail($id);

        $data = $request->validate([
            'cat_code' => [
                'required','string','max:25',
                Rule::exists(AssetCategory::class, 'cat_code')
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'type_code' => [
                'required','string','max:25',
                Rule::unique(AssetType::class, 'type_code')
                    ->ignore($m->id)
                    ->where(fn ($q) => $q->where('company_id', $companyId)
                                        ->where('cat_code', $request->input('cat_code'))),
            ],
            'type_name' => ['required','string','max:150'],
            'life_months_override'   => ['nullable','integer','min:1','max:360'],
            'depr_method_override'   => ['nullable', Rule::in(['straight_line','declining_balance','units_of_production','none'])],
            'residual_rate_override' => ['nullable','numeric','min:0','max:100'],
            'is_active'              => ['sometimes','boolean'],
            'sort_order'             => ['sometimes','integer','min:0','max:100000'],
        ]);

        $m->fill($data);
        $m->user_id        = $user?->id;
        $m->workstation_id = $request->header('X-Workstation-Id') ?? $request->ip();
        $m->save();

        return response()->json($m);
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $m = AssetType::query()->company($companyId)->findOrFail($id);
        $m->delete();

        return response()->json(['ok' => true]);
    }

    /** GET /api/assets/types/{id}/next-code  -> ICT-ACC-TRN-000001 */
    public function nextCode(Request $request, int $id)
    {
        $code = DB::selectOne('SELECT fn_next_asset_code(?) as code', [$id])->code ?? null;
        return response()->json(['code' => $code]);
    }
}
