<?php
namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Using Simple QrCode (auto-discovered)
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetController extends Controller
{
    public function index(Request $req)
    {
        $per = (int)($req->input('per', 10));
        $companyId = (int)$req->user()->company_id ?? 1;

        $sql = DB::table('assets as a')
            ->leftJoin('asset_groups as g','g.id','=','a.asset_group_id')
            ->selectRaw('a.id, a.asset_number, a.description, a.life_months, a.qty, g.group_name, a.gross_amount, a.purchased_date')
            ->where('a.company_id',$companyId);

        if ($s = trim((string)$req->input('q',''))) {
            $sql->where(function($w) use ($s) {
                $w->where('a.asset_number','ilike',"%$s%")
                  ->orWhere('a.description','ilike',"%$s%")
                  ->orWhere('g.group_name','ilike',"%$s%");
            });
        }
        if ($grp = $req->input('group_id')) $sql->where('a.asset_group_id',(int)$grp);

        $sql->orderByDesc('a.id');
        return $sql->paginate($per);
    }

      /* Creates an asset, auto-generates asset_number, writes history, writes QR.
     */
    public function store(Request $req)
    {
        $user = $req->user();
        $companyId = (int)($user?->company_id ?? 1);

        // NOTE: Frontend uses these names; we validate the minimal “main form” set
        $data = $req->validate([
            'description'           => 'required|string|max:300',
            'asset_type'            => 'required|string|max:25',    // 'depreciable' | 'non_depreciable' or lookup id
            'class_code'            => 'nullable|string|max:25',
            'category_code'         => 'nullable|string|max:25',
            'type_code'             => 'nullable|string|max:25',
            'quantity'              => 'required|numeric|min:1',

            'loan_agreement'        => 'nullable|string|max:50',    // 'Default'|'Lease'|'Loan' (we coerce to bool below)
            'include_in_audits'     => 'boolean',
            'last_audited'          => 'nullable|date',

            // optional finance/general fields you already have in the UI
            'depr_method'           => 'nullable|string|max:50',
            'workstation_id'        => 'nullable|string|max:50',
        ]);

        // Map UI → DB columns
        $row = [
            'company_id'             => $companyId,
            'description'            => (string)$data['description'],
            'class_code'             => $data['class_code']   ?? null,
            'cat_code'               => $data['category_code']?? null,
            'type_code'              => $data['type_code']    ?? null,
            'depreciation_type_code' => (string)$data['asset_type'],  // keep as provided by your lookup
            'quantity'               => (float)$data['quantity'],

            // DB says loan_agreement is boolean; interpret UI:
            // Default => false; Lease/Loan => true
            'loan_agreement'         => isset($data['loan_agreement'])
                                        ? (strtolower($data['loan_agreement']) !== 'default')
                                        : false,

            'include_in_audits'      => (bool)($data['include_in_audits'] ?? false),
            'last_audit_date'        => $data['last_audited'] ?? null,

            'asset_depr_method'      => $data['depr_method']  ?? null, // if you plan to use it
            'workstation_id'         => $data['workstation_id'] ?? ($req->header('X-Workstation') ?? gethostname()),
            'user_id'                => $user?->id ?? null,
            'created_at'             => now(),
            'updated_at'             => now(),
        ];

        // Transaction to avoid double numbers under concurrency
        $created = DB::transaction(function () use ($row, $companyId, $user) {
            // 1) Generate asset_number (yyyymmddNNNN)
            $assetNumber = $this->makeAssetNumber();

            // 2) Insert into assets
            $toInsert = $row;
            $toInsert['asset_number'] = $assetNumber;

            $assetId = DB::table('assets')->insertGetId($toInsert);

            // 3) Write history row
            DB::table('asset_history')->insert([
                'asset_id'     => $assetId,
                'company_id'   => $companyId,
                'asset_number' => $assetNumber,
                'time_stamp'   => now(),
                'description'  => 'Created',
                'comments'     => null,
                'username'     => $user?->name ?? 'system',
                'computer_name'=> $row['workstation_id'] ?? gethostname(),
                'created_at'   => now(),
            ]);

            // 4) Generate and save QR (SVG) now — store under public disk
            $this->writeQr($assetId, $assetNumber);

            return ['id' => $assetId, 'asset_number' => $assetNumber];
        });

        return response()->json($created, 201);
    }


    public function show($id)
    {
        $asset = DB::table('assets')->where('id',$id)->first();
        if (!$asset) return response()->json(['message'=>'Not found'],404);

        return response()->json([
            'asset'     => $asset,
            'service'   => DB::table('asset_service')->where('asset_id',$id)->orderByDesc('service_date')->limit(50)->get(),
            'notes'     => DB::table('asset_notes')->where('asset_id',$id)->orderByDesc('id')->limit(100)->get(),
            'history'   => DB::table('asset_history')->where('asset_id',$id)->orderByDesc('timestamp_at')->limit(100)->get(),
            'pictures'  => DB::table('asset_pictures')->where('asset_id',$id)->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function update($id, Request $req)
    {
        $data = $req->only([
            'description','asset_group_id','asset_sub_group_id','asset_comp_id',
            'qty','life_months','purchased_date','reference_no','supplier_name',
            'vat_inclusive','vat_rate','gross_amount','serialized',
            'manufacturer','brand','model','options_loan_agreement','include_in_audits','last_audited'
        ]);
        if (!$data) return response()->json(['ok'=>true]); // nothing to change
        $data['updated_at'] = now();
        DB::table('assets')->where('id',$id)->update($data);
        DB::table('asset_history')->insert([
            'asset_id'=>$id,'description'=>'Updated','user_name'=>$req->user()->name ?? 'system'
        ]);
        return response()->json(['ok'=>true]);
    }

    public function destroy($id)
    {
        DB::table('assets')->where('id',$id)->delete();
        return response()->noContent();
    }

    // ---- tabs ----
    public function addService($id, Request $req)
    {
        $data = $req->validate([
            'service_date'=>'required|date',
            'description' =>'nullable|string|max:255',
            'parts_cost'  =>'numeric|min:0',
            'labor_cost'  =>'numeric|min:0',
            'next_due_date'=>'nullable|date'
        ]);
        $data['asset_id'] = (int)$id;
        $sid = DB::table('asset_service')->insertGetId($data);
        return response()->json(['id'=>$sid],201);
    }
    public function listService($id){ return DB::table('asset_service')->where('asset_id',$id)->orderByDesc('service_date')->paginate(10); }

    public function addNote($id, Request $req)
    {
        $data = $req->validate(['note_text'=>'required|string']);
        $data['asset_id'] = (int)$id;
        $data['created_by'] = $req->user()->id ?? null;
        $nid = DB::table('asset_notes')->insertGetId($data);
        return response()->json(['id'=>$nid],201);
    }
    public function listNotes($id){ return DB::table('asset_notes')->where('asset_id',$id)->orderByDesc('id')->paginate(10); }

    public function listHistory($id){ return DB::table('asset_history')->where('asset_id',$id)->orderByDesc('timestamp_at')->paginate(10); }

    public function addPicture($id, Request $req)
    {
        $data = $req->validate([
            'url'    =>'required|url|max:500',
            'caption'=>'nullable|string|max:150'
        ]);
        $data['asset_id'] = (int)$id;
        $pid = DB::table('asset_pictures')->insertGetId($data);
        return response()->json(['id'=>$pid],201);
    }
    public function listPictures($id){ return DB::table('asset_pictures')->where('asset_id',$id)->orderByDesc('id')->paginate(10); }

/**
     * GET /assets/{id}/qr.svg
     * Streams the QR SVG; regenerates if missing.
     */
    public function qr(int $id)
    {
        $asset = DB::table('assets')->select('id','asset_number')->where('id', $id)->first();
        if (!$asset) {
            abort(404, 'Asset not found');
        }

        $relPath = "qr/assets/{$asset->asset_number}.svg";
        if (!Storage::disk('public')->exists($relPath)) {
            // regenerate if missing
            $this->writeQr($asset->id, $asset->asset_number);
        }

        $full = Storage::disk('public')->path($relPath);
        return response()->file($full, ['Content-Type' => 'image/svg+xml; charset=utf-8']);
    }

    /**
     * Make a unique asset number with pattern yyyymmddNNNN (0000..9999 daily).
     * Uses a SELECT MAX LIKE prefix within a SERIALIZABLE transaction.
     */
    private function makeAssetNumber(): string
    {
        $prefix = now()->format('Ymd'); // yyyymmdd
        // find the max suffix used today
        $max = DB::table('assets')
            ->select(DB::raw("MAX(asset_number) as max_no"))
            ->where('asset_number', 'LIKE', $prefix.'%')
            ->lockForUpdate()
            ->first();

        $nextSeq = 0;
        if ($max && $max->max_no) {
            // last 4 chars of max number → int
            $last = substr($max->max_no, -4);
            $nextSeq = max( (int)$last + 1, 0 );
        }

        $suffix = str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
        return $prefix.$suffix;
    }

    /**
     * Generate + save QR SVG for an asset under storage/app/public/qr/assets/{asset_number}.svg
     * Encodes both id and asset_number (easy to scan + lookup).
     */
    private function writeQr(int $assetId, string $assetNumber): void
    {
        $payload = json_encode([
            'id'           => $assetId,
            'asset_number' => $assetNumber,
            'type'         => 'asset',
            'v'            => 1, // for future-proofing
        ], JSON_UNESCAPED_SLASHES);

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($payload);

        $relPath = "qr/assets/{$assetNumber}.svg";
        Storage::disk('public')->put($relPath, $svg);
    }



}
