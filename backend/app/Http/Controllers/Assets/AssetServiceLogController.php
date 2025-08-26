<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetServiceLogController extends Controller
{
    /** GET /assets/{id}/service-logs?per=&page= */
    public function index($assetId, Request $req)
    {
        $per  = max(1, min(100, (int)$req->query('per', 10)));
        $page = max(1, (int)$req->query('page', 1));

        $qb = DB::table('asset_service_logs')
                ->where('asset_id', $assetId)
                ->orderByDesc('service_date')
                ->orderByDesc('id');

        $rows = $qb->paginate($per, ['*'], 'page', $page);

        return response()->json([
            'data'         => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page'    => $rows->lastPage(),
            'total'        => $rows->total(),
        ]);
    }

    /** POST /assets/{id}/service-logs */
    public function store($assetId, Request $req)
    {
        $data = $req->validate([
            'service_date' => 'required|date',
            'description'  => 'nullable|string',
            'parts_cost'   => 'nullable|numeric|min:0',
            'labor_cost'   => 'nullable|numeric|min:0',
            'next_due_date'=> 'nullable|date',
        ]);

        DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
        DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

        $id = DB::table('asset_service_logs')->insertGetId([
            'asset_id'      => $assetId,
            'service_date'  => $data['service_date'],
            'description'   => $data['description'] ?? null,
            'parts_cost'    => $data['parts_cost'] ?? 0,
            'labor_cost'    => $data['labor_cost'] ?? 0,
            'next_due_date' => $data['next_due_date'] ?? null,
            'user_id'       => $req->user()->id,
            'workstation_id'=> $req->ip(),
        ]);

        return response()->json(
            DB::table('asset_service_logs')->where('id',$id)->first(),
            201
        );
    }

    /** PATCH /service-logs/{logId} */
    public function update($logId, Request $req)
    {
        $data = $req->validate([
            'service_date' => 'nullable|date',
            'description'  => 'nullable|string',
            'parts_cost'   => 'nullable|numeric|min:0',
            'labor_cost'   => 'nullable|numeric|min:0',
            'next_due_date'=> 'nullable|date',
        ]);

        DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
        DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

        $set = $data;
        if (!empty($set)) {
            $set['updated_at'] = now();
            DB::table('asset_service_logs')->where('id',$logId)->update($set);
        }

        return response()->json(
            DB::table('asset_service_logs')->where('id',$logId)->first()
        );
    }

    /** DELETE /service-logs/{logId} */
    public function destroy($logId, Request $req)
    {
        DB::statement("SELECT set_config('app.user_id', ?, false)", [$req->user()->id]);
        DB::statement("SELECT set_config('app.workstation_id', ?, false)", [$req->ip()]);

        DB::table('asset_service_logs')->where('id',$logId)->delete();
        return response()->json(['ok'=>true]);
    }
}
