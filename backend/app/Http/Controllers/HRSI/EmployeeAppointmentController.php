<?php

namespace App\Http\Controllers\HRSI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\HRSI\Employee;
use App\Models\HRSI\EmployeeAppointment;
use Illuminate\Support\Facades\DB;

class EmployeeAppointmentController extends Controller
{
    public function index(int $id) {
        $e = Employee::findOrFail($id);
        return response()->json(
            $e->appointments()->with(['orgUnit','position','rank','jobStatus'])->orderByDesc('id')->get()
        );
    }

    public function store(Request $r, int $id) {
        $e = Employee::findOrFail($id);
        $data = $r->validate([
            'org_unit_id'     => ['required','exists:org_units,id'],
            'position_id'     => ['nullable','exists:job_positions,id'],
            'rank_id'         => ['nullable','exists:ranks,id'],
            'job_status_id'   => ['nullable','exists:job_statuses,id'],
            'employment_type' => ['required', Rule::in(['teaching','non_teaching','admin','both'])],
            'title_override'  => ['nullable','string','max:150'],
            'fte'             => ['nullable','numeric','min:0','max:1'],
            'is_primary'      => ['boolean'],
            'valid_from'      => ['required','date'],
            'valid_to'        => ['nullable','date','after:valid_from'],
        ]);
        $m = $e->appointments()->create([
            'org_unit_id'=>$data['org_unit_id'],
            'position_id'=>$data['position_id']??null,
            'rank_id'=>$data['rank_id']??null,
            'job_status_id'=>$data['job_status_id']??null,
            'employment_type'=>$data['employment_type'],
            'title_override'=>$data['title_override']??null,
            'fte'=>$data['fte']??1.0,
            'is_primary'=>$data['is_primary']??false,
            'valid_during'=>DB::raw("daterange('{$data['valid_from']}'::date, ".($data['valid_to']? "'{$data['valid_to']}'::date": 'NULL').")")
        ]);
        return response()->json($m,201);
    }

    public function update(Request $r, int $id, int $apptId) {
        $e = Employee::findOrFail($id); $m = $e->appointments()->findOrFail($apptId);
        $data = $r->validate([
            'org_unit_id'     => ['nullable','exists:org_units,id'],
            'position_id'     => ['nullable','exists:job_positions,id'],
            'rank_id'         => ['nullable','exists:ranks,id'],
            'job_status_id'   => ['nullable','exists:job_statuses,id'],
            'employment_type' => ['nullable', Rule::in(['teaching','non_teaching','admin','both'])],
            'title_override'  => ['nullable','string','max:150'],
            'fte'             => ['nullable','numeric','min:0','max:1'],
            'is_primary'      => ['boolean'],
            'valid_from'      => ['nullable','date'],
            'valid_to'        => ['nullable','date','after:valid_from'],
        ]);

        if (isset($data['valid_from']) || array_key_exists('valid_to',$data)) {
            $from = $data['valid_from'] ?? explode(',', trim($m->valid_during, '[]()'))[0];
            $to   = array_key_exists('valid_to',$data) ? $data['valid_to'] : null;
            $data['valid_during'] = DB::raw("daterange('{$from}'::date, ".($to? "'{$to}'::date": 'NULL').")");
            unset($data['valid_from'],$data['valid_to']);
        }
        $m->fill($data)->save();
        return response()->json($m);
    }

    public function destroy(int $id, int $apptId) {
        Employee::findOrFail($id)->appointments()->findOrFail($apptId)->delete();
        return response()->json(['ok'=>true]);
    }
}
