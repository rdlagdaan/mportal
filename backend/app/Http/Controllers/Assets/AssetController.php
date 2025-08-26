<?php
namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function store(Request $req)
    {
        $companyId = (int)$req->user()->company_id ?? 1;
        $data = $req->validate([
            'asset_number'      => 'required|string|max:50',
            'description'       => 'required|string|max:255',
            'asset_group_id'    => 'required|integer',
            'asset_sub_group_id'=> 'nullable|integer',
            'asset_comp_id'     => 'nullable|integer',
            'qty'               => 'required|numeric|min:0.01',
            'life_months'       => 'required|integer|min:1|max:600',
            'purchased_date'    => 'nullable|date',
            'reference_no'      => 'nullable|string|max:100',
            'supplier_name'     => 'nullable|string|max:150',
            'vat_inclusive'     => 'boolean',
            'vat_rate'          => 'nullable|numeric|min:0|max:100',
            'gross_amount'      => 'numeric|min:0',
            'serialized'        => 'boolean',
            'manufacturer'      => 'nullable|string|max:150',
            'brand'             => 'nullable|string|max:150',
            'model'             => 'nullable|string|max:150',
        ]);
        $data['company_id'] = $companyId;
        $id = DB::table('assets')->insertGetId($data);
        DB::table('asset_history')->insert([
            'asset_id'=>$id,'description'=>'Created','user_name'=>$req->user()->name ?? 'system'
        ]);
        return response()->json(['id'=>$id], 201);
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
}
