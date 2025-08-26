<?php
namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GatepassController extends Controller
{
    public function index(Request $req){
        $per=(int)$req->input('per',20);
        $q=trim((string)$req->input('q',''));
        $sql=DB::table('inv_gatepass')->select('id','gatepass_no','purpose','requested_by','status','created_at')->orderByDesc('id');
        if($q!=='') $sql->where(fn($w)=>$w->where('gatepass_no','ilike',"%$q%")->orWhere('purpose','ilike',"%$q%")->orWhere('requested_by','ilike',"%$q%"));
        return $sql->paginate($per);
    }
    public function store(Request $req){
        $data=$req->validate(['gatepass_no'=>'required|string|max:50','purpose'=>'nullable|string|max:255','requested_by'=>'nullable|string|max:120']);
        $id=DB::table('inv_gatepass')->insertGetId($data+['status'=>'OPEN','created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['id'=>$id],201);
    }
    public function show($id){
        $hdr=DB::table('inv_gatepass')->where('id',$id)->first();
        if(!$hdr) return response()->json(['message'=>'Not found'],404);
        $lines=DB::table('inv_gatepass_items as li')
            ->leftJoin('assets as a','a.id','=','li.asset_id')
            ->leftJoin('asset_custody as c','c.id','=','li.custody_id')
            ->selectRaw('li.id, li.description, li.qty, li.remarks, li.asset_id, a.asset_number, c.seq_id')
            ->where('gatepass_id',$id)->get();
        return ['header'=>$hdr,'items'=>$lines];
    }
    public function update($id, Request $req){
        $data=$req->validate(['purpose'=>'sometimes|string|max:255','requested_by'=>'sometimes|string|max:120','status'=>'sometimes|in:OPEN,SUBMITTED,CLOSED']);
        if($data) DB::table('inv_gatepass')->where('id',$id)->update($data+['updated_at'=>now()]);
        return ['ok'=>true];
    }
    public function destroy($id){ DB::table('inv_gatepass')->where('id',$id)->delete(); return response()->noContent(); }

    public function addItem($id, Request $req){
        $data=$req->validate([
            'asset_id'=>'nullable|integer',
            'custody_id'=>'nullable|integer',
            'description'=>'nullable|string|max:255',
            'qty'=>'numeric|min:0.01',
            'remarks'=>'nullable|string|max:255',
        ]);
        $data['gatepass_id']=(int)$id;
        $iid=DB::table('inv_gatepass_items')->insertGetId($data);
        return ['id'=>$iid];
    }
    public function removeItem($itemId){ DB::table('inv_gatepass_items')->where('id',$itemId)->delete(); return response()->noContent(); }

    public function submit($id){ DB::table('inv_gatepass')->where('id',$id)->update(['status'=>'SUBMITTED','updated_at'=>now()]); return ['ok'=>true]; }
    public function close($id){ DB::table('inv_gatepass')->where('id',$id)->update(['status'=>'CLOSED','updated_at'=>now()]); return ['ok'=>true]; }
}
