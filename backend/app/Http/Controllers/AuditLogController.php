<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $r)
    {
        $table = $r->query('table');
        $id = $r->query('id');
        abort_if(!$table || !$id, 400, 'table & id required');

        $rows = DB::table('audit_logs')
            ->where('table_name','like',"%{$table}%")
            ->where('record_pk', (string)$id)
            ->orderByDesc('changed_at')
            ->limit(200)
            ->get()
            ->map(function($x){
                $summary = '';
                if ($x->action==='UPDATE' && $x->old_values && $x->new_values) { $summary = 'Updated fields'; }
                elseif ($x->action==='INSERT') $summary='Created';
                elseif ($x->action==='DELETE') $summary='Deleted';
                return array_merge((array)$x, ['summary'=>$summary]);
            });

        return response()->json($rows);
    }
}
