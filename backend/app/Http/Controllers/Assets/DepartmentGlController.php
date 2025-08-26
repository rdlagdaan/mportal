<?php
namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentGlController extends Controller
{
    // LIST + SEARCH (dept GL code + dept name) with pagination
    public function index(Request $req)
    {
        $per  = (int)($req->input('per', 20));
        $page = (int)($req->input('page', 1));
        $gl   = trim((string)$req->input('gl',''));
        $name = trim((string)$req->input('name',''));

        $sql = DB::table('asset_department_gl')->select([
            'id', 'gl_account', 'department', 'active'
        ]);

        if ($gl !== '')   $sql->where('gl_account', 'ilike', "%$gl%");
        if ($name !== '') $sql->where('department', 'ilike', "%$name%");

        $sql->orderBy('gl_account');
        $pg = $sql->paginate($per, ['*'], 'page', $page);

        // Present active as "Y"/"N" for the grid
        $pg->getCollection()->transform(function ($r) {
            $r->trans_flag = $r->active ? 'Y' : 'N';
            return $r;
        });

        return $pg;
    }

    // CREATE
    public function store(Request $req)
    {
        $data = $req->validate([
            'gl_account' => 'required|string|max:40',
            'department' => 'required|string|max:80',
            'trans_flag' => 'nullable|in:Y,N' // UI field; map to active
        ]);

        $insert = [
            'gl_account' => $data['gl_account'],
            'department' => $data['department'],
            'active'     => ($data['trans_flag'] ?? 'Y') === 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('asset_department_gl')->insertGetId($insert);
        return response()->json(['id' => $id], 201);
    }

    // UPDATE
    public function update($id, Request $req)
    {
        $data = $req->validate([
            'gl_account' => 'sometimes|string|max:40',
            'department' => 'sometimes|string|max:80',
            'trans_flag' => 'sometimes|in:Y,N'
        ]);

        $update = [];
        if (array_key_exists('gl_account', $data)) $update['gl_account'] = $data['gl_account'];
        if (array_key_exists('department', $data)) $update['department'] = $data['department'];
        if (array_key_exists('trans_flag', $data)) $update['active'] = $data['trans_flag'] === 'Y';
        if (!$update) return response()->json(['ok'=>true]);

        $update['updated_at'] = now();
        DB::table('asset_department_gl')->where('id',$id)->update($update);
        return response()->json(['ok'=>true]);
    }

    // DELETE
    public function destroy($id)
    {
        DB::table('asset_department_gl')->where('id',$id)->delete();
        return response()->noContent();
    }
}
