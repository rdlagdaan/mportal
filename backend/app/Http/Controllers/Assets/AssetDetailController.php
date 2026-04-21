<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetDetailController extends Controller
{
    /**
     * Whitelist sortable columns (always referenced as alias "a")
     */
    private array $sortable = [
        'asset_no'      => "COALESCE(a.asset_no, a.asset_number)",
        'asset_number'  => "COALESCE(a.asset_no, a.asset_number)",
        'description'   => 'a.description',
        'purchase_date' => 'a.purchase_date',
        'gross_amount'  => 'a.gross_amount',
        'created_at'    => 'a.created_at',
        'updated_at'    => 'a.updated_at',
    ];

    /**
     * Resolve the actual assets table within the "assets" schema.
     * Prefers assets.asset_details; falls back to assets.assets.
     */
    private function assetsTable(): string
    {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }

        try {
            $row = DB::selectOne("select to_regclass('assets.asset_details') as t");
            if ($row && $row->t) {
                return $resolved = 'assets.asset_details';
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $row = DB::selectOne("select to_regclass('assets.assets') as t");
            if ($row && $row->t) {
                return $resolved = 'assets.assets';
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // last resort (keeps original name if detection fails)
        return $resolved = 'assets.asset_details';
    }

    /**
     * Return the search view if present (either schema), else null.
     */
    private function assetSearchView(): ?string
    {
        foreach (['assets.asset_detail_search', 'public.asset_detail_search'] as $qn) {
            try {
                $row = DB::selectOne("select to_regclass(?) as t", [$qn]);
                if ($row && $row->t) {
                    return $qn;
                }
            } catch (\Throwable $e) {
                // ignore and continue
            }
        }
        return null;
    }

    /**
     * Get the set of column names for a qualified table (schema.table)
     */
    private function tableColumns(string $qualified): array
    {
        $parts = explode('.', $qualified, 2);
        $schema = $parts[0] ?? 'public';
        $name   = $parts[1] ?? $qualified;

        $rows = DB::select(
            "select column_name from information_schema.columns where table_schema = ? and table_name = ?",
            [$schema, $name]
        );
        $cols = [];
        foreach ($rows as $r) {
            $cols[strtolower($r->column_name)] = true;
        }
        return $cols;
    }

    /** GET /assets?q=&per=&page=&order=&dir= */
public function index(Request $req)
{
    $this->authorize('viewAny', \App\Models\Assets\Asset::class);

    // Basics
    $q    = trim((string) $req->query('q', ''));
    $per  = max(1, min(200, (int) $req->query('per', 10)));
    $page = max(1, (int) $req->query('page', 1));
    $dir  = strtolower((string) $req->query('dir', 'desc'));
    $dir  = in_array($dir, ['asc','desc'], true) ? $dir : 'desc';

    // Company scope (request -> user -> 0)  ⬅ matches your store()
    $companyId = (int) ($req->query('company_id')
        ?? optional($req->user())->company_id
        ?? 0);

    // Resolve table and discover available columns BEFORE choosing ORDER BY
    $assetsTable = $this->assetsTable();              // e.g., assets.assets OR assets.asset_details
    $cols        = $this->tableColumns($assetsTable); // ['asset_number'=>true, 'created_at'=>true, ...]
    $searchView  = $this->assetSearchView();          // e.g., assets.asset_detail_search OR null

    // Requested order (default to created_at to avoid 500 when purchase_date doesn't exist)
    $requestedOrder = (string) $req->query('order', 'created_at');

    // Build a safe ORDER BY expression based on what actually exists
    switch ($requestedOrder) {
        case 'asset_no':
        case 'asset_number':
            $orderBy = "COALESCE(a.asset_no, a.asset_number)";
            break;

        case 'description':
            $orderBy = isset($cols['description']) ? 'a.description' : 'a.created_at';
            break;

        case 'purchase_date':
            $orderBy = isset($cols['purchase_date']) ? 'a.purchase_date' : 'a.created_at';
            break;

        case 'gross_amount':
            $orderBy = isset($cols['gross_amount']) ? 'a.gross_amount' : 'a.created_at';
            break;

        case 'updated_at':
            $orderBy = 'a.updated_at';
            break;

        case 'created_at':
        default:
            $orderBy = 'a.created_at';
            break;
    }

    // Base query
    $qb = DB::table($assetsTable . ' as a')
        ->where('a.company_id', $companyId);

    // Search
    if ($q !== '') {
        $like = '%'.$q.'%';

        if ($searchView) {
            $qb->join($searchView . ' as s', 's.asset_id', '=', 'a.id')
               ->where(function ($w) use ($like) {
                   $w->whereRaw("s.asset_no_l ILIKE ?", [$like])
                     ->orWhereRaw("s.description_l ILIKE ?", [$like])
                     ->orWhereRaw("s.reference_l ILIKE ?", [$like])
                     ->orWhereRaw("s.supplier_name_l ILIKE ?", [$like])
                     ->orWhereRaw("s.serial_no_l ILIKE ?", [$like]);
               });
        } else {
            $qb->where(function ($w) use ($like, $cols) {
                if (isset($cols['asset_no']))       $w->orWhereRaw('a.asset_no ILIKE ?', [$like]);
                if (isset($cols['asset_number']))   $w->orWhereRaw('a.asset_number ILIKE ?', [$like]);
                if (isset($cols['description']))    $w->orWhereRaw('a.description ILIKE ?', [$like]);
                if (isset($cols['reference']))      $w->orWhereRaw('COALESCE(a.reference, \'\') ILIKE ?', [$like]);
                if (isset($cols['supplier_name']))  $w->orWhereRaw('COALESCE(a.supplier_name, \'\') ILIKE ?', [$like]);
                if (isset($cols['serial_no']))      $w->orWhereRaw('COALESCE(a.serial_no, \'\') ILIKE ?', [$like]);
            });
        }
    }

    // Exclude archived by default
    if (!$req->boolean('include_archived', false) && isset($cols['status'])) {
        $qb->where('a.status', '!=', 'ARCHIVED');
    }

    // Order + paginate
    $qb->orderByRaw("$orderBy $dir");

    $rows = $qb
    ->selectRaw("a.*, COALESCE(a.asset_no, a.asset_number) AS asset_no")
    ->paginate($per, ['a.*'], 'page', $page);


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
        $asset = DB::table($this->assetsTable())->where('id', $id)->first();
        abort_if(!$asset, 404);
        return response()->json($asset);
    }




public function store(Request $req)
{
    // 1) Validate incoming fields
    $data = $req->validate([
        'description'       => 'required|string|max:5000',
        'class_code'        => 'required|string|max:25',
        'category_code'     => 'required|string|max:25',   // UI name; mapped to DB cat_code
        'type_code'         => 'required|string|max:25',

        'asset_type'        => 'nullable|string|max:40',
        'depr_method'       => 'nullable|string|max:40',
        'quantity'          => 'required|integer|min:1',
        'loan_agreement'    => 'nullable|string|max:80',
        'include_in_audits' => 'boolean',
        'last_audited'      => 'nullable|date',
    ]);

    // Company scope (prefer explicit from request, then user, default to 0)
    $companyId = (int) ($req->input('company_id')
        ?? optional($req->user())->company_id
        ?? 0);

    $assetsTable = $this->assetsTable(); // e.g. "assets.assets"

    // 2) DUPLICATE GUARD — same company, class, cat, type, and description (case/space-insensitive)
    $dup = DB::table($assetsTable.' as a')
        ->where('a.company_id', $companyId)
        ->where('a.class_code', $data['class_code'])
        ->where('a.cat_code',  $data['category_code'])         // <-- note: cat_code in DB
        ->where('a.type_code', $data['type_code'])
        ->whereRaw('lower(btrim(a.description)) = lower(btrim(?))', [$data['description']])
        ->select(['a.id','a.asset_number'])
        ->first();

    if ($dup) {
        return response()->json([
            'message' => 'Duplicate asset: an item with the same class, category, type, and description already exists.',
            'errors'  => ['duplicate' => ["asset_number {$dup->asset_number} (id {$dup->id})"]],
        ], 422);
    }

    // 3) Generate asset_number: CLASS-YYYYMMDD-#### (per-class, per-day sequence)
    $class = strtoupper($data['class_code']);
    $date  = now()->format('Ymd');

    // Find current max #### for this company+class+date
    // Works even if older rows used other formats (regex filters only matching rows)
    $pattern = $class . '-' . $date . '-%';
    $maxSeq = DB::table($assetsTable)
        ->where('company_id', $companyId)
        ->where('class_code', $data['class_code'])
        ->where('asset_number', 'like', $pattern)
        ->selectRaw("MAX( COALESCE( (regexp_match(asset_number, '(\\d{4})$'))[1]::int, 0) ) AS max_seq")
        ->value('max_seq') ?? 0;

    $nextSeq = (int)$maxSeq + 1;
    $seqStr  = str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    $assetNumber = "{$class}-{$date}-{$seqStr}";

    // 4) Build insert payload (map category_code → cat_code)
    $insert = [
        'company_id'        => $companyId,
        'asset_number'      => $assetNumber,
        'description'       => $data['description'],
        'class_code'        => $data['class_code'],
        'cat_code'          => $data['category_code'],     // <-- correct DB column
        'type_code'         => $data['type_code'],
        'asset_type'        => $data['asset_type']        ?? null,
        'depr_method'       => $data['depr_method']       ?? 'straight_line',
        'quantity'          => (int)$data['quantity'],
        'loan_agreement'    => $data['loan_agreement']    ?? 'Default',
        'include_in_audits' => (bool)($data['include_in_audits'] ?? false),
        'last_audited'      => $data['last_audited']      ?? null,
        'status'            => 'ACTIVE',
        'user_id'           => $req->user()->id ?? null,
        'workstation_id'    => is_numeric(optional(auth()->user())->workstation_id ?? null)
                                ? (int) auth()->user()->workstation_id
                                : null,
        'created_at'        => now(),
        'updated_at'        => now(),
    ];

    // Defensive: keep only real columns, but NEVER drop asset_number
    [$schema, $table] = str_contains($assetsTable, '.')
        ? explode('.', $assetsTable, 2)
        : ['public', $assetsTable];

    $cols = DB::select(
        'select lower(column_name) as column_name from information_schema.columns
         where table_schema = ? and table_name = ?',
        [$schema, $table]
    );
    $existing = array_flip(array_map(fn($r) => $r->column_name, $cols));
    $filtered = array_intersect_key($insert, array_change_key_case($existing, CASE_LOWER));
    $filtered['asset_number'] = $insert['asset_number']; // re-assert

    // 5) Insert
    return DB::transaction(function () use ($req, $assetsTable, $filtered) {
        try {
            DB::statement("SELECT set_config('app.user_id', ?, false)", [(string) ($req->user()->id ?? '')]);
            DB::statement("SELECT set_config('app.workstation_id', ?, false)", [
                (string) (optional(auth()->user())->workstation_id ?? '')
            ]);
            DB::statement("SELECT set_config('app.ip', ?, false)", [$req->ip()]);
        } catch (\Throwable $e) {}

        try {
            $id  = DB::table($assetsTable)->insertGetId($filtered);
            $row = DB::table($assetsTable)->where('id', $id)->first();
            return response()->json($row, 201);
        } catch (\Throwable $e) {
            Log::error('Asset insert failed', [
                'table' => $assetsTable,
                'error' => $e->getMessage(),
                'payload_keys' => array_keys($filtered),
            ]);
            abort(500, 'Asset save failed: '.$e->getMessage());
        }
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

        $assetsTable = $this->assetsTable();

        return DB::transaction(function () use ($req, $id, $data, $assetsTable) {
            DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
            DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

            if (!empty($data)) {
                $data['updated_at'] = now();
                DB::table($assetsTable)->where('id', $id)->update($data);

                try {
                    DB::statement("SELECT fn_refresh_asset_search_row(?)", [$id]);
                } catch (\Throwable $e) {
                    try {
                        DB::statement("SELECT assets.fn_refresh_asset_search_row(?)", [$id]);
                    } catch (\Throwable $e2) {}
                }
            }

            // Optional: (re)create children if serialized & quantity > 1
            $row = DB::table($assetsTable)->where('id', $id)->first();
            if ($row && property_exists($row, 'is_serialized') && $row->is_serialized) {
                $qtyInt = (int) floor((float) ($row->quantity ?? 1));
                if ($qtyInt > 1) {
                    $hasChildren = DB::table($assetsTable)->where('parent_id', $id)->exists();
                    if (!$hasChildren) {
                        for ($i=1; $i<=$qtyInt; $i++) {
                            $suffix  = '-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                            $childNo = ($row->asset_no ?? 'ASST') . $suffix;

                            $childId = DB::table($assetsTable)->insertGetId([
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
                                'workstation_id' => auth()->user()->workstation_id ?? null,
                            ]);

                            try {
                                DB::statement("SELECT fn_refresh_asset_search_row(?)", [$childId]);
                            } catch (\Throwable $e) {
                                try {
                                    DB::statement("SELECT assets.fn_refresh_asset_search_row(?)", [$childId]);
                                } catch (\Throwable $e2) {}
                            }
                        }
                    }
                }
            }

            $asset = DB::table($assetsTable)->where('id', $id)->first();
            return response()->json($asset);
        });
    }

    /** DELETE /assets/{id} (soft by default; ?hard=1 for hard delete) */
    public function destroy($id, Request $req)
    {
        DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
        DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

        $assetsTable = $this->assetsTable();
        $hard = $req->boolean('hard', false);

        if ($hard) {
            DB::table($assetsTable)->where('id', $id)->delete();
            return response()->json(['ok' => true, 'hard_deleted' => true]);
        }

        DB::table($assetsTable)->where('id', $id)->update([
            'status'     => 'ARCHIVED',
            'updated_at' => now(),
        ]);

        try {
            DB::statement("SELECT fn_refresh_asset_search_row(?)", [$id]);
        } catch (\Throwable $e) {
            try {
                DB::statement("SELECT assets.fn_refresh_asset_search_row(?)", [$id]);
            } catch (\Throwable $e2) {}
        }

        return response()->json(['ok' => true, 'archived' => true]);
    }

    /** POST /assets/{id}/picture  (multipart/form-data: file) */
    public function uploadPicture($id, Request $req)
    {
        $req->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $assetsTable = $this->assetsTable();

        $asset = DB::table($assetsTable)->where('id', $id)->first();
        abort_if(!$asset, 404);

        $file = $req->file('file');
        $ext  = $file->getClientOriginalExtension();
        $name = ($asset->asset_no ?? "asset_$id") . '-' . time() . '.' . $ext;

        $path = $file->storeAs('asset_pics', $name, 'public'); // storage/app/public/asset_pics/...
        $url  = Storage::disk('public')->url($path);

        DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
        DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

        DB::table($assetsTable)->where('id', $id)->update([
            'picture_path' => $url,
            'updated_at'   => now(),
        ]);

        try {
            DB::statement("SELECT fn_refresh_asset_search_row(?)", [$id]);
        } catch (\Throwable $e) {
            try {
                DB::statement("SELECT assets.fn_refresh_asset_search_row(?)", [$id]);
            } catch (\Throwable $e2) {}
        }

        return response()->json(['picture_path' => $url]);
    }

    /** GET /assets/{id}/children */
    public function children($id)
    {
        $rows = DB::table($this->assetsTable())
            ->where('parent_id', $id)
            ->orderBy('asset_no')
            ->get();

        return response()->json($rows);
    }

/** GET /api/assets/{id}/qr.svg → SVG QR for asset_number (no file stored) */
public function qrSvg($id, Request $req)
{
    $assetsTable = $this->assetsTable();
    $asset = DB::table($assetsTable)->where('id', $id)->first();
    abort_if(!$asset, 404);

    $payload = (string) ($asset->asset_number ?? '');
    abort_if($payload === '', 404, 'Asset has no number yet.');

    // Optional sizing via ?size= (64–1024, default 256)
    $size = (int) $req->query('size', 256);
    $size = max(64, min(1024, $size));

    // Generate crisp SVG (no margin so it fits small stickers nicely)
    $svg = QrCode::format('svg')
        ->size($size)
        ->margin(0)
        ->errorCorrection('M')
        ->generate($payload);

    return response($svg, 200)
        ->header('Content-Type', 'image/svg+xml; charset=utf-8')
        ->header('Cache-Control', 'no-store');
}



}
