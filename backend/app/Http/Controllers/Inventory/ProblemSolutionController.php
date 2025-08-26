<?php
namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProblemSolutionController extends Controller
{
    public function index(Request $req) {
        $per = (int)$req->input('per', 20);
        $q   = trim((string)$req->input('q',''));
        $sql = DB::table('inv_problem_solution')->select('id','title','solution','updated_at');
        if ($q !== '') $sql->where(fn($w)=>$w->where('title','ilike',"%$q%")->orWhere('solution','ilike',"%$q%"));
        $sql->orderByDesc('updated_at');
        return $sql->paginate($per);
    }
    public function store(Request $req){
        $data = $req->validate(['title'=>'required|string|max:150','solution'=>'required|string']);
        $id = DB::table('inv_problem_solution')->insertGetId($data+['created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['id'=>$id],201);
    }
    public function update($id, Request $req){
        $data = $req->validate(['title'=>'sometimes|string|max:150','solution'=>'sometimes|string']);
        if ($data) DB::table('inv_problem_solution')->where('id',$id)->update($data+['updated_at'=>now()]);
        return response()->json(['ok'=>true]);
    }
    public function destroy($id){ DB::table('inv_problem_solution')->where('id',$id)->delete(); return response()->noContent(); }
}
