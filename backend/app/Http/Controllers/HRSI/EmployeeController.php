<?php

namespace App\Http\Controllers\HRSI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use App\Models\HRSI\Employee;
use App\Models\HRSI\OrgUnit;
use App\Models\HRSI\EmployeeAppointment;
use App\Models\HRSI\EmployeeEducation;
use App\Models\HRSI\EmployeeRelative;
use App\Models\HRSI\EmployeeGovId;
use App\Models\HRSI\Lookups\Religion;
use App\Models\HRSI\Lookups\CivilStatus;
use App\Models\HRSI\Lookups\JobStatus;
use App\Models\HRSI\Lookups\Rank;
use App\Models\HRSI\Lookups\JobPosition;
use App\Models\HRSI\Lookups\EmployeeRole;
use App\Models\HRSI\Lookups\GovIdType;
use App\Models\HRSI\Lookups\EducationLevel;
use App\Models\HRSI\Lookups\RelativeType;

class EmployeeController extends Controller
{
    public function lookups()
    {
        return response()->json([
            'org_units'        => OrgUnit::orderBy('name')->get(['id','code','name','type']),
            'employee_rolles'  => EmployeeRole::orderBy('name')->get(['id','code','name']),
            'ranks'            => Rank::orderBy('name')->get(['id','code','name']),
            'job_statuses'     => JobStatus::orderBy('name')->get(['id','code','name']),
            'job_positions'    => JobPosition::orderBy('name')->get(['id','code','name']),
            'religions'        => Religion::orderBy('name')->get(['id','code','name']),
            'civil_statuses'   => CivilStatus::orderBy('name')->get(['id','code','name']),
            'gov_id_types'     => GovIdType::orderBy('name')->get(['id','code','name']),
            'education_levels' => EducationLevel::orderBy('id')->get(['id','code','name']),
            'relative_types'   => RelativeType::orderBy('id')->get(['id','code','name']),
        ]);
    }

 public function index(Request $request)
{
    $per  = max(1, (int)$request->input('per_page', 10));
    $q    = trim((string) $request->input('q',''));

    $rows = Employee::query()
        ->when($q, fn($w) =>
            $w->whereRaw('lower(employee_number) like ?', ['%'.mb_strtolower($q).'%'])
              ->orWhereRaw('lower(last_name) like ?',      ['%'.mb_strtolower($q).'%'])
              ->orWhereRaw('lower(first_name) like ?',     ['%'.mb_strtolower($q).'%'])
        )
        ->orderBy('last_name')
        ->paginate($per);

    return response()->json($rows);   // ← DO NOT wrap it in ["data" => $rows]
}


    public function store(Request $r)
    {
        $data = $r->validate([
            'employee_number' => ['required','string','max:30','unique:employees,employee_number'],
            'last_name'       => ['required','string','max:100'],
            'first_name'      => ['required','string','max:100'],
            'middle_name'     => ['nullable','string','max:100'],
            'suffix'          => ['nullable','string','max:20'],
            'sex'             => ['required', Rule::in(['male','female','other','prefer_not_to_say'])],
            'date_of_birth'   => ['required','date'],
            'religion_id'     => ['nullable','exists:religions,id'],
            'user_id'         => ['nullable','exists:users,id','unique:employees,user_id'],
        ]);

        $m = new Employee($data);
        $m->save();

        return response()->json($m, 201);
    }

    public function show(int $id)
    {
        $e = Employee::with([
            'contactsActive','presentAddress','permanentAddress',
            'govIds.type','relatives.type','educations.level',
            'appointments.orgUnit','appointments.position','appointments.rank','appointments.jobStatus',
            'currentAppointment.orgUnit','currentRoles.role'
        ])->findOrFail($id);

        return response()->json([
            'personal' => $e->only(['id','employee_number','last_name','first_name','middle_name','suffix','sex','date_of_birth','religion_id']),
            'contact'  => [
                'contacts' => $e->contactsActive,
                'present_address'   => $e->presentAddress,
                'permanent_address' => $e->permanentAddress,
            ],
            'govt'     => $e->govIds->map(fn($g)=>[
                'type' => $g->type->code, 'number'=>$g->id_number, 'issued_at'=>$g->issued_at, 'expires_at'=>$g->expires_at
            ]),
            'family'   => $e->relatives->map(fn($r)=>[
                'type'=>$r->type->code,'full_name'=>$r->full_name,'birth_date'=>$r->birth_date,
                'occupation'=>$r->occupation,'employer'=>$r->employer,'address'=>$r->address,'contact_no'=>$r->contact_no
            ]),
            'education'=> $e->educations->map(fn($ed)=>[
                'id'=>$ed->id,'level'=>$ed->level->code,'school_name'=>$ed->school_name,'school_location'=>$ed->school_location,
                'date_from'=>$ed->date_from,'date_to'=>$ed->date_to,'is_completed'=>$ed->is_completed,
                'course'=>$ed->course,'honors'=>$ed->honors,
            ]),
            'appointments' => $e->appointments->map(fn($a)=>[
                'id'=>$a->id,'org_unit'=>$a->orgUnit?->name,'position'=>$a->position_name,'employment_type'=>$a->employment_type,
                'rank'=>$a->rank?->name,'job_status'=>$a->jobStatus?->name,'valid_during'=>$a->valid_during,'fte'=>$a->fte,'is_primary'=>$a->is_primary
            ]),
        ]);
    }

    public function update(Request $r, int $id)
    {
        $e = Employee::findOrFail($id);
        $data = $r->validate([
            'employee_number' => ['required','string','max:30', Rule::unique('employees','employee_number')->ignore($e->id)],
            'last_name'       => ['required','string','max:100'],
            'first_name'      => ['required','string','max:100'],
            'middle_name'     => ['nullable','string','max:100'],
            'suffix'          => ['nullable','string','max:20'],
            'sex'             => ['required', Rule::in(['male','female','other','prefer_not_to_say'])],
            'date_of_birth'   => ['required','date'],
            'religion_id'     => ['nullable','exists:religions,id'],
            'user_id'         => ['nullable','exists:users,id', Rule::unique('employees','user_id')->ignore($e->id)],
        ]);
        $e->fill($data)->save();
        return response()->json(['ok'=>true]);
    }

    public function destroy(int $id)
    {
        Employee::findOrFail($id)->delete();
        return response()->json(['ok'=>true]);
    }

    // --------- Partial updaters for tabs ----------
    public function saveContact(Request $r, int $id)
    {
        $e = Employee::findOrFail($id);

        // Example: upsert one mobile + two emails + present/permanent address
        $data = $r->validate([
            'contacts'                  => ['array'],
            'contacts.*.type'           => ['required','string'],
            'contacts.*.value'          => ['required','string','max:255'],
            'contacts.*.is_primary'     => ['boolean'],
            'present_address'           => ['array'],
            'permanent_address'         => ['array'],
        ]);

        DB::transaction(function () use ($e, $data) {
            if (!empty($data['contacts'])) {
                foreach ($data['contacts'] as $c) {
                    // Close any open range of same (type,value)
                    DB::statement("
                      UPDATE employee_contacts
                      SET valid_during = daterange(lower(valid_during), current_date)
                      WHERE employee_id=? AND type=? AND value=? AND upper_inf(valid_during)
                    ", [$e->id, $c['type'], $c['value']]);

                    DB::table('employee_contacts')->insert([
                        'employee_id'=>$e->id,'type'=>$c['type'],'value'=>$c['value'],
                        'is_primary'=>$c['is_primary']??false,
                        'valid_during'=>DB::raw("daterange(current_date, NULL)"),
                    ]);
                }
            }

            foreach (['present_address'=>'present','permanent_address'=>'permanent'] as $key=>$atype) {
                if (empty($data[$key])) continue;
                $a = $data[$key];
                DB::statement("
                  UPDATE employee_addresses
                  SET valid_during = daterange(lower(valid_during), current_date)
                  WHERE employee_id=? AND address_type=? AND upper_inf(valid_during)
                ", [$e->id, $atype]);

                DB::table('employee_addresses')->insert([
                    'employee_id'=>$e->id,'address_type'=>$atype,
                    'address_line'=>$a['address_line']??'',
                    'district'=>$a['district']??null,'barangay'=>$a['barangay']??null,
                    'city_municipality'=>$a['city_municipality']??'','province'=>$a['province']??null,
                    'region'=>$a['region']??null,'postal_code'=>$a['postal_code']??null,
                    'country_code'=>$a['country_code']??'PH','valid_during'=>DB::raw("daterange(current_date, NULL)"),
                ]);
            }
        });

        return response()->json(['ok'=>true]);
    }

    public function saveGovt(Request $r, int $id)
    {
        $e = Employee::findOrFail($id);
        $rows = $r->validate([
            'gov_ids' => ['required','array'],
            'gov_ids.*.type_code' => ['required','string'],
            'gov_ids.*.id_number' => ['required','string','max:120'],
            'gov_ids.*.issued_at' => ['nullable','date'],
            'gov_ids.*.expires_at'=> ['nullable','date'],
        ]);
        DB::transaction(function() use ($e,$rows){
            foreach ($rows['gov_ids'] as $g) {
                $typeId = DB::table('government_id_types')->where('code',$g['type_code'])->value('id');
                if (!$typeId) continue;
                EmployeeGovId::updateOrCreate(
                    ['employee_id'=>$e->id,'type_id'=>$typeId],
                    ['id_number'=>$g['id_number'],'issued_at'=>$g['issued_at']??null,'expires_at'=>$g['expires_at']??null]
                );
            }
        });
        return response()->json(['ok'=>true]);
    }

    public function saveFamily(Request $r, int $id)
    {
        $e = Employee::findOrFail($id);
        $rows = $r->validate([
            'relatives' => ['array'],
            'relatives.*.type_code' => ['required','string'],
            'relatives.*.full_name' => ['required','string','max:200'],
            'relatives.*.birth_date'=> ['nullable','date'],
            'relatives.*.occupation'=> ['nullable','string','max:150'],
            'relatives.*.employer'  => ['nullable','string','max:200'],
            'relatives.*.address'   => ['nullable','string','max:300'],
            'relatives.*.contact_no'=> ['nullable','string','max:80'],
            'relatives.*.is_dependent'=>['boolean'],
        ]);

        DB::transaction(function() use ($e,$rows){
            // naive approach: wipe & insert
            $e->relatives()->delete();
            foreach ($rows['relatives'] ?? [] as $r) {
                $typeId = DB::table('relative_types')->where('code',$r['type_code'])->value('id');
                if (!$typeId) continue;
                $e->relatives()->create([
                    'type_id'=>$typeId,'full_name'=>$r['full_name'],'birth_date'=>$r['birth_date']??null,
                    'occupation'=>$r['occupation']??null,'employer'=>$r['employer']??null,
                    'address'=>$r['address']??null,'contact_no'=>$r['contact_no']??null,
                    'is_dependent'=>$r['is_dependent']??false
                ]);
            }
        });

        return response()->json(['ok'=>true]);
    }
}
