<?php
namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisposalRetirementController extends Controller
{
    public function index(Request $req){
        $per=(int)$req->input('per',20);
        $sql=DB::table('inv_disposal_retirement as dr')
          ->leftJoin('assets as a','a.id','=','dr.asset_id')
          ->selectRaw('dr.id, dr.type, dr.doc_no, dr.date_txn, dr.remarks, a.asset_number, a.description, a.status')
          ->orderByDesc('dr.id');
        return $sql->paginate($per);
    }
    public function store(Request $req){
        $data=$req->validate([
          'asset_id'=>'required|integer',
          'type'=>'required|in:SALE,DISPOSAL,RETIREMENT',
          'doc_no'=>'nullable|string|max:50',
          'date_txn'=>'required|date',
          'remarks'=>'nullable|string',
        ]);
        $id=DB::table('inv_disposal_retirement')->insertGetId($data+['created_at'=>now()]);
        return ['id'=>$id];
    }
    public function update($id, Request $req){
        $data=$req->validate([
          'doc_no'=>'sometimes|string|max:50',
          'date_txn'=>'sometimes|date',
          'remarks'=>'sometimes|string'
        ]);
        if($data) DB::table('inv_disposal_retirement')->where('id',$id)->update($data+['updated_at'=>now()]);
        return ['ok'=>true];
    }
    public function destroy($id){ DB::table('inv_disposal_retirement')->where('id',$id)->delete(); return response()->noContent(); }

    // Finalize lifecycle: mark asset status and (optionally) stop future depreciation by setting life to months_spent
    public function finalize($id){
        $row=DB::table('inv_disposal_retirement')->where('id',$id)->first();
        if(!$row) return response()->json(['message'=>'Not found'],404);

        $status = match($row->type){ 'SALE'=>'SOLD', 'DISPOSAL'=>'DISPOSED', default=>'RETIRED' };
        DB::table('assets')->where('id',$row->asset_id)->update(['status'=>$status, 'updated_at'=>now()]);
        return ['ok'=>true,'asset_status'=>$status];
    }
}
