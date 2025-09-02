<?php
namespace App\Http\Controllers\mdo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    // GET /api/mdo/queue?company_id=1&station=triage
    public function index(Request $r)
    {
        $companyId = (int) $r->query('company_id', 1);
        $station   = $r->query('station'); // optional
        $q = DB::table('mdo.v_open_queue')->where('company_id', $companyId);
        if ($station) $q->where('station', $station);
        return response()->json($q->get());
    }
}
