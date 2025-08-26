<?php
namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IssuanceReturnController extends Controller
{
    // --- Screen A: Grid with filters --------------------------------------
    public function index(Request $req)
    {
        $per  = (int)($req->input('per', 20));
        $page = (int)($req->input('page', 1));

        $assetNumber = trim((string)$req->input('asset_number',''));
        $desc        = trim((string)$req->input('description',''));
        $groupName   = trim((string)$req->input('group_name',''));
        $dept        = trim((string)$req->input('department',''));
        $loc         = trim((string)$req->input('location',''));
        $cust        = trim((string)$req->input('custodian',''));

        $sql = DB::table('asset_custody as ac')
            ->join('assets as a','a.id','=','ac.asset_id')
            ->leftJoin('asset_groups as g','g.id','=','a.asset_group_id')
            ->selectRaw("
                ac.id as custody_id,
                a.id  as asset_id,
                a.asset_number,
                ac.seq_id,
                a.description,
                COALESCE(g.group_name,'') as group_name,
                a.purchased_date,
                ac.custodian,
                ac.issued_by,
                ac.status,
                ac.serial_no,
                ac.department,
                ac.location
            ");

        if ($assetNumber !== '') $sql->where('a.asset_number','ilike',"%$assetNumber%");
        if ($desc        !== '') $sql->where('a.description','ilike',"%$desc%");
        if ($groupName   !== '') $sql->where('g.group_name','ilike',"%$groupName%");
        if ($dept        !== '') $sql->where('ac.department','ilike',"%$dept%");
        if ($loc         !== '') $sql->where('ac.location','ilike',"%$loc%");
        if ($cust        !== '') $sql->where('ac.custodian','ilike',"%$cust%");

        $sql->orderBy('a.asset_number')->orderBy('ac.seq_id');

        return $sql->paginate($per, ['*'], 'page', $page);
    }

    // --- Screen B: Assign (batch or individual) ---------------------------
    public function assign($assetId, Request $req)
    {
        $data = $req->validate([
            'by_batch'   => 'boolean',              // true = all IN_STOCK rows; false = one seq
            'seq_id'     => 'nullable|integer',     // required if by_batch=false
            'department' => 'required|string|max:120',
            'location'   => 'nullable|string|max:120',
            'custodian'  => 'nullable|string|max:120',
            'issued_by'  => 'nullable|string|max:120',
            'remarks'    => 'nullable|string|max:500',
        ]);
        $userId = $req->user()->id ?? null;
        $now    = now();

        $rows = DB::table('asset_custody')
            ->where('asset_id',$assetId)
            ->when(!($data['by_batch'] ?? false), fn($q)=>$q->where('seq_id',$data['seq_id'] ?? -1))
            ->where('status','IN_STOCK')
            ->get(['id','seq_id']);

        if ($rows->isEmpty()) return response()->json(['message'=>'No units to assign'], 422);

        foreach ($rows as $r) {
            DB::table('asset_custody')->where('id',$r->id)->update([
                'department' => $data['department'],
                'location'   => $data['location'] ?? null,
                'custodian'  => $data['custodian'] ?? null,
                'issued_by'  => $data['issued_by'] ?? ($req->user()->name ?? null),
                'status'     => 'ASSIGNED',
                'issued_at'  => $now,
                'updated_at' => $now,
            ]);
            DB::table('inv_issuance_return')->insert([
                'type'       => 'ISSUANCE',
                'asset_id'   => (int)$assetId,
                'seq_id'     => $r->seq_id,
                'serial_no'  => null,
                'department' => $data['department'],
                'location'   => $data['location'] ?? null,
                'custodian'  => $data['custodian'] ?? null,
                'issued_by'  => $data['issued_by'] ?? ($req->user()->name ?? null),
                'remarks'    => $data['remarks'] ?? null,
                'txn_at'     => $now,
                'user_id'    => $userId,
                'created_at' => $now,
            ]);
        }
        return response()->json(['ok'=>true,'count'=>$rows->count()]);
    }

    // --- Return (batch or individual) -------------------------------------
    public function return($assetId, Request $req)
    {
        $data = $req->validate([
            'by_batch' => 'boolean',
            'seq_id'   => 'nullable|integer',
            'remarks'  => 'nullable|string|max:500',
        ]);
        $userId = $req->user()->id ?? null;
        $now    = now();

        $rows = DB::table('asset_custody')
            ->where('asset_id',$assetId)
            ->when(!($data['by_batch'] ?? false), fn($q)=>$q->where('seq_id',$data['seq_id'] ?? -1))
            ->where('status','ASSIGNED')
            ->get(['id','seq_id','serial_no','department','location','custodian','issued_by']);

        if ($rows->isEmpty()) return response()->json(['message'=>'No units to return'], 422);

        foreach ($rows as $r) {
            DB::table('asset_custody')->where('id',$r->id)->update([
                'status'     => 'IN_STOCK',
                'returned_at'=> $now,
                'updated_at' => $now,
            ]);
            DB::table('inv_issuance_return')->insert([
                'type'       => 'RETURN',
                'asset_id'   => (int)$assetId,
                'seq_id'     => $r->seq_id,
                'serial_no'  => $r->serial_no,
                'department' => $r->department,
                'location'   => $r->location,
                'custodian'  => $r->custodian,
                'issued_by'  => $r->issued_by,
                'remarks'    => $data['remarks'] ?? null,
                'txn_at'     => $now,
                'user_id'    => $userId,
                'created_at' => $now,
            ]);
        }
        return response()->json(['ok'=>true,'count'=>$rows->count()]);
    }

    // --- Screen C: Assign serial numbers (auto-increment from start) -------
    public function assignSerials($assetId, Request $req)
    {
        $data = $req->validate([
            'start_serial' => 'required|string|max:80',
        ]);
        // Parse numeric tail; keep prefix
        if (!preg_match('/^(.*?)(\d+)$/', $data['start_serial'], $m)) {
            return response()->json(['message'=>'Start serial must end with digits (e.g., ABC0001)'], 422);
        }
        [$full,$prefix,$num] = $m;
        $pad = strlen($num);
        $cur = (int)$num;

        $rows = DB::table('asset_custody')
            ->where('asset_id',$assetId)
            ->orderBy('seq_id')
            ->get(['id','serial_no']);

        $count = 0;
        foreach ($rows as $r) {
            if ($r->serial_no) continue;
            $cur++;
            $serial = $prefix . str_pad((string)$cur, $pad, '0', STR_PAD_LEFT);
            DB::table('asset_custody')->where('id',$r->id)->update([
                'serial_no'  => $serial,
                'updated_at' => now(),
            ]);
            DB::table('inv_issuance_return')->insert([
                'type'     => 'ASSIGN_SN',
                'asset_id' => (int)$assetId,
                'seq_id'   => null,
                'serial_no'=> $serial,
                'txn_at'   => now(),
                'user_id'  => $req->user()->id ?? null,
                'created_at'=> now(),
            ]);
            $count++;
        }
        return response()->json(['ok'=>true,'assigned'=>$count]);
    }

    public function journal(Request $req)
    {
        return DB::table('inv_issuance_return')->orderByDesc('id')->paginate(50);
    }
}
