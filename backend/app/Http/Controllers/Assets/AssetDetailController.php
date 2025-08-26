<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Assets\Asset;

class AssetDetailController extends Controller
{
    // Whitelist sortable columns
    private array $sortable = [
        'asset_no'      => 'a.asset_no',
        'description'   => 'a.description',
        'purchase_date' => 'a.purchase_date',
        'gross_amount'  => 'a.gross_amount',
        'created_at'    => 'a.created_at',
        'updated_at'    => 'a.updated_at',
    ];

    /** GET /assets?q=&per=&page=&order=&dir= */
    public function index(Request $req)
    {
        $this->authorize('viewAny', \App\Models\Assets\Asset::class);

        $q    = strtolower(trim((string)$req->query('q','')));
        $per  = max(1, min(200, (int)$req->query('per', 10)));
        $page = max(1, (int)$req->query('page', 1));

        $order = (string)$req->query('order', 'purchase_date');
        $dir   = strtolower((string)$req->query('dir', 'desc'));
        $dir   = in_array($dir, ['asc','desc'], true) ? $dir : 'desc';
        $orderBy = $this->sortable[$order] ?? $this->sortable['purchase_date'];

        $companyId = $req->user()->company_id ?? 1;

        $qb = DB::table('asset_details as a')
            ->join('asset_detail_search as s', 's.asset_id', '=', 'a.id')
            ->where('a.company_id', $companyId);

        if ($q !== '') {
            $qb->where(function($w) use ($q) {
                $like = "%{$q}%";
                $w->whereRaw("s.asset_no_l ILIKE ?", [$like])
                  ->orWhereRaw("s.description_l ILIKE ?", [$like])
                  ->orWhereRaw("s.reference_l ILIKE ?", [$like])
                  ->orWhereRaw("s.supplier_name_l ILIKE ?", [$like])
                  ->orWhereRaw("s.serial_no_l ILIKE ?", [$like]);
            });
        }

        // Avoid archived by default (toggle via ?include_archived=1)
        if (!$req->boolean('include_archived', false)) {
            $qb->where('a.status', '!=', 'ARCHIVED');
        }

        $qb->orderByRaw("$orderBy $dir");

        // Use paginator to return standard payload
        $rows = $qb->select('a.*')->paginate($per, ['a.*'], 'page', $page);

        return response()->json([
            'data'         => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page'    => $rows->lastPage(),
            'total'        => $rows->total(),
        ]);
    }

    /** GET /assets/{id} */
    public function show($id)
    {
        $asset = DB::table('asset_details')->where('id', $id)->first();
        abort_if(!$asset, 404);
        return response()->json($asset);
    }

    /** POST /assets */
    public function store(Request $req)
    {
        $data = $req->validate([
            'description'      => 'required|string|max:5000',
            'type_code'        => 'required|string|max:25',
            'life_months'      => 'nullable|integer|min:1',
            'depr_method'      => 'nullable|string|max:40',
            'residual_rate'    => 'nullable|numeric|min:0|max:100',

            'quantity'         => 'required|numeric|min:1',
            'is_serialized'    => 'boolean',
            'serial_no'        => 'nullable|string|max:120',

            'purchase_date'    => 'nullable|date',
            'in_service_date'  => 'nullable|date',
            'warranty_expires' => 'nullable|date',
            'reference'        => 'nullable|string|max:80',
            'supplier_id'      => 'nullable|integer',
            'supplier_name'    => 'nullable|string|max:200',
            'vat_inclusive'    => 'boolean',
            'vat_rate'         => 'nullable|numeric|min:0|max:100',
            'gross_amount'     => 'nullable|numeric|min:0',

            'loan_agreement'   => 'nullable|string|max:80',
            'include_in_audits'=> 'boolean',
            'last_audited'     => 'nullable|date',

            'manufacturer'     => 'nullable|string|max:120',
            'brand'            => 'nullable|string|max:120',
            'model'            => 'nullable|string|max:120',

            'status'           => 'nullable|string|max:30',
            'prefix'           => 'nullable|string|max:10', // for asset number generator
        ]);

        $companyId = $req->user()->company_id ?? 1;

        return DB::transaction(function () use ($req, $data, $companyId) {
            // Audit context
            DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
            DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

            // Generate asset number once for the parent
            $prefix = $data['prefix'] ?? 'ASST';
            $row    = DB::selectOne("SELECT fn_next_asset_no(:cid, :pref) AS no", ['cid'=>$companyId, 'pref'=>$prefix]);
            $assetNo = $row->no;

            $parentId = DB::table('asset_details')->insertGetId([
                'company_id'       => $companyId,
                'asset_no'         => $assetNo,
                'description'      => $data['description'],
                'type_code'        => $data['type_code'],
                'life_months'      => $data['life_months'] ?? 60,
                'depr_method'      => $data['depr_method'] ?? 'straight_line',
                'residual_rate'    => $data['residual_rate'] ?? 0,

                'quantity'         => $data['quantity'],
                'is_serialized'    => (bool)($data['is_serialized'] ?? false),
                'serial_no'        => $data['serial_no'] ?? null,
                'manufacturer'     => $data['manufacturer'] ?? null,
                'brand'            => $data['brand'] ?? null,
                'model'            => $data['model'] ?? null,

                'purchase_date'    => $data['purchase_date'] ?? null,
                'in_service_date'  => $data['in_service_date'] ?? null,
                'warranty_expires' => $data['warranty_expires'] ?? null,
                'reference'        => $data['reference'] ?? null,
                'supplier_id'      => $data['supplier_id'] ?? null,
                'supplier_name'    => $data['supplier_name'] ?? null,
                'vat_inclusive'    => (bool)($data['vat_inclusive'] ?? false),
                'vat_rate'         => $data['vat_rate'] ?? null,
                'gross_amount'     => $data['gross_amount'] ?? 0,

                'loan_agreement'   => $data['loan_agreement'] ?? 'Default',
                'include_in_audits'=> (bool)($data['include_in_audits'] ?? false),
                'last_audited'     => $data['last_audited'] ?? null,

                'status'           => $data['status'] ?? 'ACTIVE',
                'user_id'          => $req->user()->id,
                'workstation_id'   => $req->ip(),
            ]);

            // Refresh search row for parent
            DB::statement("SELECT fn_refresh_asset_search_row(?)", [$parentId]);

            // Optional: create children when serialized + quantity > 1
            $qtyInt = (int)floor((float)$data['quantity']);
            $isSerialized = (bool)($data['is_serialized'] ?? false);

            if ($isSerialized && $qtyInt > 1) {
                for ($i=1; $i<=$qtyInt; $i++) {
                    $suffix  = '-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                    $childNo = $assetNo . $suffix;
                    $childId = DB::table('asset_details')->insertGetId([
                        'company_id'     => $companyId,
                        'asset_no'       => $childNo,
                        'description'    => $data['description'],
                        'type_code'      => $data['type_code'],
                        'life_months'    => $data['life_months'] ?? 60,
                        'depr_method'    => $data['depr_method'] ?? 'straight_line',
                        'residual_rate'  => $data['residual_rate'] ?? 0,
                        'quantity'       => 1,
                        'is_serialized'  => true,
                        'parent_id'      => $parentId,
                        'serial_no'      => null,  // can be set later per unit
                        'status'         => 'ACTIVE',
                        'user_id'        => $req->user()->id,
                        'workstation_id' => $req->ip(),
                    ]);
                    DB::statement("SELECT fn_refresh_asset_search_row(?)", [$childId]);
                }
            }

            $asset = DB::table('asset_details')->where('id', $parentId)->first();
            return response()->json($asset, 201);
        });
    }

    /** PATCH /assets/{id} */
    public function update($id, Request $req)
    {
        $data = $req->validate([
            'description'      => 'sometimes|required|string|max:5000',
            'type_code'        => 'sometimes|required|string|max:25',
            'life_months'      => 'nullable|integer|min:1',
            'depr_method'      => 'nullable|string|max:40',
            'residual_rate'    => 'nullable|numeric|min:0|max:100',
            'quantity'         => 'nullable|numeric|min:1',
            'is_serialized'    => 'nullable|boolean',
            'serial_no'        => 'nullable|string|max:120',
            'purchase_date'    => 'nullable|date',
            'in_service_date'  => 'nullable|date',
            'warranty_expires' => 'nullable|date',
            'reference'        => 'nullable|string|max:80',
            'supplier_id'      => 'nullable|integer',
            'supplier_name'    => 'nullable|string|max:200',
            'vat_inclusive'    => 'nullable|boolean',
            'vat_rate'         => 'nullable|numeric|min:0|max:100',
            'gross_amount'     => 'nullable|numeric|min:0',
            'loan_agreement'   => 'nullable|string|max:80',
            'include_in_audits'=> 'nullable|boolean',
            'last_audited'     => 'nullable|date',
            'manufacturer'     => 'nullable|string|max:120',
            'brand'            => 'nullable|string|max:120',
            'model'            => 'nullable|string|max:120',
            'status'           => 'nullable|string|max:30',
        ]);

        return DB::transaction(function () use ($req, $id, $data) {
            DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
            DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

            $set = [];
            foreach ($data as $k => $v) { $set[$k] = $v; }
            if (!empty($set)) {
                $set['updated_at'] = now();
                DB::table('asset_details')->where('id', $id)->update($set);
                DB::statement("SELECT fn_refresh_asset_search_row(?)", [$id]);
            }

            // Optional: if turning serialized on & no children exist, create them
            if (array_key_exists('is_serialized', $data) || array_key_exists('quantity', $data)) {
                $row = DB::table('asset_details')->where('id', $id)->first();
                if ($row && $row->is_serialized && (int)floor((float)($row->quantity ?? 1)) > 1) {
                    $hasChildren = DB::table('asset_details')->where('parent_id', $id)->exists();
                    if (!$hasChildren) {
                        $qtyInt = (int)floor((float)$row->quantity);
                        for ($i=1; $i<=$qtyInt; $i++) {
                            $suffix  = '-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                            $childNo = $row->asset_no . $suffix;
                            $childId = DB::table('asset_details')->insertGetId([
                                'company_id'     => $row->company_id,
                                'asset_no'       => $childNo,
                                'description'    => $row->description,
                                'type_code'      => $row->type_code,
                                'life_months'    => $row->life_months,
                                'depr_method'    => $row->depr_method,
                                'residual_rate'  => $row->residual_rate,
                                'quantity'       => 1,
                                'is_serialized'  => true,
                                'parent_id'      => $id,
                                'status'         => 'ACTIVE',
                                'user_id'        => $req->user()->id,
                                'workstation_id' => $req->ip(),
                            ]);
                            DB::statement("SELECT fn_refresh_asset_search_row(?)", [$childId]);
                        }
                    }
                }
            }

            $asset = DB::table('asset_details')->where('id', $id)->first();
            return response()->json($asset);
        });
    }

    /** DELETE /assets/{id}  (soft by default) */
    public function destroy($id, Request $req)
    {
        DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
        DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

        $hard = $req->boolean('hard', false);
        if ($hard) {
            DB::table('asset_details')->where('id', $id)->delete(); // cascades to search table via trigger
            return response()->json(['ok'=>true,'hard_deleted'=>true]);
        }

        DB::table('asset_details')->where('id', $id)->update([
            'status' => 'ARCHIVED',
            'updated_at' => now(),
        ]);
        DB::statement("SELECT fn_refresh_asset_search_row(?)", [$id]);
        return response()->json(['ok'=>true,'archived'=>true]);
    }

    /** POST /assets/{id}/picture  multipart/form-data: file */
    public function uploadPicture($id, Request $req)
    {
        $req->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120'
        ]);

        $asset = DB::table('asset_details')->where('id',$id)->first();
        abort_if(!$asset, 404);

        $file = $req->file('file');
        $ext  = $file->getClientOriginalExtension();
        $name = ($asset->asset_no ?? "asset_$id") . '-' . time() . '.' . $ext;

        $path = $file->storeAs('asset_pics', $name, 'public'); // storage/app/public/asset_pics/...
        $url  = Storage::disk('public')->url($path);

        DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
        DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

        DB::table('asset_details')->where('id',$id)->update([
            'picture_path' => $url,
            'updated_at'   => now(),
        ]);

        DB::statement("SELECT fn_refresh_asset_search_row(?)", [$id]);

        return response()->json(['picture_path'=>$url]);
    }

    /** GET /assets/{id}/children */
    public function children($id)
    {
        $rows = DB::table('asset_details')->where('parent_id',$id)->orderBy('asset_no')->get();
        return response()->json($rows);
    }
}
