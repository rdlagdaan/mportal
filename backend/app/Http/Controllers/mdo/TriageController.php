<?php
namespace App\Http\Controllers\mdo;

use App\Http\Controllers\Controller;
use App\Models\mdo\Encounter;
use App\Models\mdo\Vital;
use App\Models\mdo\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TriageController extends Controller
{
    // POST /api/mdo/triage/encounters
    public function openEncounter(Request $r)
    {
        $data = $r->validate([
            'company_id'       => ['required','integer','min:1'],
            'person_id'        => ['required','integer','min:1'],
            'provider_id'      => ['nullable','integer','min:1'],
            'chief_complaint'  => ['nullable','string','max:2000'],
            'encounter_type'   => ['nullable', Rule::in(['clinic','dental','immunization'])],
        ]);

        $enc = Encounter::create([
            'company_id'      => $data['company_id'],
            'person_id'       => $data['person_id'],
            'provider_id'     => $data['provider_id'] ?? null,
            'encounter_type'  => $data['encounter_type'] ?? 'clinic',
            'status'          => 'open',
            'chief_complaint' => $data['chief_complaint'] ?? null,
        ]);

        return response()->json(['encounter_id'=>$enc->id], 201);
    }

    // POST /api/mdo/triage/vitals
    public function addVitals(Request $r)
    {
        $data = $r->validate([
            'encounter_id' => ['required','integer','min:1','exists:mdo.encounters,id'],
            'systolic'     => ['nullable','integer','between:50,260'],
            'diastolic'    => ['nullable','integer','between:30,160'],
            'hr'           => ['nullable','integer','between:30,240'],
            'rr'           => ['nullable','integer','between:6,80'],
            'temp_c'       => ['nullable','numeric','between:30,45'],
            'spo2'         => ['nullable','integer','between:50,100'],
            'height_cm'    => ['nullable','numeric','between:50,250'],
            'weight_kg'    => ['nullable','numeric','between:2,500'],
            'taken_at'     => ['nullable','date'],
        ]);

        Vital::create($data + ['taken_at' => $data['taken_at'] ?? now()]);
        return response()->json(['ok'=>true], 201);
    }

    // POST /api/mdo/triage/queue
    public function sendToQueue(Request $r)
    {
        $data = $r->validate([
            'company_id'   => ['required','integer','min:1'],
            'encounter_id' => ['required','integer','min:1','exists:mdo.encounters,id'],
            'station'      => ['nullable', Rule::in(['triage','consult','dental','pharmacy','lab'])],
        ]);
        $station = $data['station'] ?? 'consult';

        return DB::transaction(function () use ($data, $station) {
            // Compute next position within (company, station, waiting)
            $next = DB::table('mdo.queues')
                ->where('company_id', $data['company_id'])
                ->where('station', $station)
                ->where('status', 'waiting')
                ->max('position');
            $pos = is_null($next) ? 1 : ($next + 1);

            $id = DB::table('mdo.queues')->insertGetId([
                'company_id'   => $data['company_id'],
                'encounter_id' => $data['encounter_id'],
                'station'      => $station,
                'position'     => $pos,
                'status'       => 'waiting',
                'queued_at'    => now(),
                'updated_at'   => now(),
            ]);

            return response()->json(['queue_id'=>$id,'position'=>$pos], 201);
        });
    }

    // GET /api/mdo/triage/encounters/{id}
    public function summary($id)
    {
        $row = DB::table('mdo.v_encounter_summary')->where('encounter_id', $id)->first();
        if (!$row) return response()->json(['message'=>'Not found'], 404);
        return response()->json($row);
    }



    public function list(Request $r)
    {
        $perPage = max(1, min(100, (int) $r->query('per_page', 10)));
        $page    = max(1, (int) $r->query('page', 1));
        $q       = trim((string) $r->query('q', ''));
        $company = (int) $r->query('company_id', 1);
        $order   = (string) $r->query('order', 'recent'); // recent|name|id

        $base = DB::table('mdo.encounters as e')
            ->join('mdo.persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('mdo.providers as pr', 'pr.id', '=', 'e.provider_id')
            ->where('e.company_id', $company)
            ->where('e.encounter_type', 'clinic')
            ->select([
                'e.id','e.status','e.started_at','e.chief_complaint',
                'p.full_name as person_name','p.id as person_id',
                'pr.name as provider_name',
            ]);

        if ($q !== '') {
            // search by person name or id
            $base->where(function($w) use ($q){
                $w->where('p.full_name','ilike',"%{$q}%")
                ->orWhere('e.id', (int) $q);
            });
        }

        if ($order === 'name') {
            $base->orderBy('p.full_name')->orderByDesc('e.started_at');
        } elseif ($order === 'id') {
            $base->orderByDesc('e.id');
        } else { // recent
            $base->orderByDesc('e.started_at');
        }

        $total = (clone $base)->count();
        $rows  = $base->offset(($page-1)*$perPage)->limit($perPage)->get();

        return response()->json([
            'data' => $rows,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ]);
    }

    public function searchPersons(Request $r)
    {
        $company = (int) $r->query('company_id', 1);
        $q       = trim((string) $r->query('q',''));
        $rows = DB::table('mdo.persons')
            ->where('company_id', $company)
            ->when($q !== '', fn($w) => $w->where('full_name','ilike',"%{$q}%"))
            ->orderBy('full_name')
            ->limit(30)
            ->get(['id','full_name']);
        return response()->json($rows);
    }





}
