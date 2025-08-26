<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetGroupController extends Controller
{
    // GET /api/assets/groups?q=&per_page=&page=&order=
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $q       = $request->input('q', $request->input('search'));
        $order   = $request->input('order', 'group_code');
        $perPage = max(1, (int)($request->input('per_page', 10)));

        $rows = AssetGroup::query()
            ->company($companyId)
            ->when($request->boolean('active_only', false), fn($qq) => $qq->where('is_active', true))
            ->search($q)
            ->sorted($order)
            ->paginate($perPage);

        return response()->json($rows);
    }

    // POST /api/assets/groups
    public function store(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $data = $request->validate([
            'group_code'  => [
                'required', 'string', 'max:50',
                Rule::unique('asset_groups', 'group_code')
                    ->where(fn($q) => $q->where('company_id', $companyId)),
            ],
            'group_name'        => ['required', 'string', 'max:150'],
            'name'              => ['required', 'string', 'max:150'],
            'default_life_months' => ['required', 'integer', 'min:1', 'max:360'],
            'default_depr_method' => ['required', Rule::in(['straight_line','declining_balance','units_of_production','none'])],
            'residual_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active'         => ['sometimes', 'boolean'],
            'sort_order'        => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ]);

        $group = new AssetGroup($data);
        $group->company_id     = $companyId;
        $group->user_id        = $user?->id;
        $group->workstation_id = $request->header('X-Workstation-Id') ?? $request->ip();
        $group->save();

        return response()->json($group, 201);
    }

    // PATCH /api/assets/groups/{id}
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $group = AssetGroup::query()->company($companyId)->findOrFail($id);

        $data = $request->validate([
            'group_code'  => [
                'required', 'string', 'max:50',
                Rule::unique('asset_groups', 'group_code')
                    ->ignore($group->id)
                    ->where(fn($q) => $q->where('company_id', $companyId)),
            ],
            'group_name'        => ['required', 'string', 'max:150'],
            'name'              => ['required', 'string', 'max:150'],
            'default_life_months' => ['required', 'integer', 'min:1', 'max:360'],
            'default_depr_method' => ['required', Rule::in(['straight_line','declining_balance','units_of_production','none'])],
            'residual_rate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active'         => ['sometimes', 'boolean'],
            'sort_order'        => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ]);

        $group->fill($data);
        $group->user_id        = $user?->id;
        $group->workstation_id = $request->header('X-Workstation-Id') ?? $request->ip();
        $group->save();

        return response()->json($group);
    }

    // DELETE /api/assets/groups/{id}
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $group = AssetGroup::query()->company($companyId)->findOrFail($id);
        $group->delete();

        return response()->json(['ok' => true]);
    }
}
