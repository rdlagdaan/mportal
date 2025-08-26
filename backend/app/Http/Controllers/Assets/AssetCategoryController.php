<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Assets\AssetClass;
use App\Events\AssetCategorySaved;
use App\Models\Assets\AssetType;
use Illuminate\Database\QueryException;

class AssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        $user      = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        // now accepts ?class_code=CLS-F (string), not class_id
        $classCode = trim((string) $request->input('class_code', ''));
        $q         = $request->input('q');
        $order     = $request->input('order', 'cat_code');
        $per       = max(1, (int) $request->input('per_page', 10));

        $rows = AssetCategory::query()
            ->company($companyId)
            // if you had a scope forClass($id), replace with inline filter by code
            ->when($classCode !== '', fn($qb) => $qb->where('class_code', $classCode))
            ->search($q)
            ->sorted($order)
            ->paginate($per);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $user      = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $data = $request->validate([
            'class_code' => [
                'required','string','max:25',
                Rule::exists(AssetClass::class, 'class_code')
                    ->where(fn($q) => $q->where('company_id', $companyId)),
            ],
            'cat_code' => [
                'required','string','max:25',
                Rule::unique(AssetCategory::class, 'cat_code')
                    ->where(fn($q) => $q->where('company_id', $companyId)
                                        ->where('class_code', $request->input('class_code'))),
            ],
            'cat_name'   => ['required','string','max:150'],
            'is_active'  => ['sometimes','boolean'],
            'sort_order' => ['sometimes','integer','min:0','max:100000'],
        ]);

        $m = new AssetCategory($data);
        $m->company_id    = $companyId;
        $m->user_id       = $user?->id;
        $m->workstation_id = $request->header('X-Workstation-Id') ?? $request->ip();
        $m->save();
        event(new AssetCategorySaved(
            $m->only(['class_code','cat_code','cat_name','is_active']),
            (int) $companyId
        ));

        return response()->json($m, 201);
    }

    public function update(Request $request, int $id)
    {
        $user      = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $m = AssetCategory::query()->company($companyId)->findOrFail($id);

        $data = $request->validate([
            'class_code' => [
                'required','string','max:25',
                Rule::exists(AssetClass::class, 'class_code')
                    ->where(fn($q) => $q->where('company_id', $companyId)),
            ],
            'cat_code' => [
                'required','string','max:25',
                Rule::unique(AssetCategory::class, 'cat_code')
                    ->ignore($m->id)
                    ->where(fn($q) => $q->where('company_id', $companyId)
                                        ->where('class_code', $request->input('class_code'))),
            ],
            'cat_name'   => ['required','string','max:150'],
            'is_active'  => ['sometimes','boolean'],
            'sort_order' => ['sometimes','integer','min:0','max:100000'],
        ]);

        // Guard: if this category has types, don't allow changing cat_code or class_code
        $incomingClass = strtoupper(trim($request->input('class_code')));
        $incomingCat   = strtoupper(trim($request->input('cat_code')));

        if ($incomingClass !== $m->class_code || $incomingCat !== $m->cat_code) {
            $hasTypes = AssetType::query()
                ->company($companyId)
                ->where('cat_code', $m->cat_code)
                ->exists();

            if ($hasTypes) {
                return response()->json([
                    'message' => "This category has asset types. You can edit the name and status, but you can't change its Class or Category Code."
                ], 409);
            }
        }

        $m->fill($data);
        $m->user_id        = $user?->id;
        $m->workstation_id = $request->header('X-Workstation-Id') ?? $request->ip();
        $m->save();
        event(new AssetCategorySaved(
            $m->only(['class_code','cat_code','cat_name','is_active']),
            (int) $companyId
        ));

        return response()->json($m);
    }

    public function destroy(Request $request, int $id)
    {
        $user      = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $m = AssetCategory::query()
            ->company($companyId)
            ->findOrFail($id);

        try {
            $m->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => "You cannot delete this category because it has asset types linked to it. Remove or reassign those types first."
                ], 409);
            }
            throw $e;
        }

        return response()->json(['ok' => true]);
    }
}
