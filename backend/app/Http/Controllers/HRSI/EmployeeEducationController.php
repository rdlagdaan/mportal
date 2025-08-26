<?php

namespace App\Http\Controllers\HRSI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\HRSI\Employee;
use App\Models\HRSI\EmployeeEducation;
use Illuminate\Support\Facades\DB;

class EmployeeEducationController extends Controller
{
    public function index(int $id) {
        $e = Employee::findOrFail($id);
        return response()->json(
            $e->educations()->with('level')->orderBy('date_from','desc')->get()
        );
    }

    public function store(Request $r, int $id) {
        $e = Employee::findOrFail($id);
        $data = $r->validate([
            'level_code'     => ['required','string'],
            'school_name'    => ['required','string','max:200'],
            'school_location'=> ['nullable','string','max:200'],
            'date_from'      => ['nullable','date'],
            'date_to'        => ['nullable','date','after_or_equal:date_from'],
            'is_completed'   => ['boolean'],
            'course'         => ['nullable','string','max:200'],
            'honors'         => ['nullable','string','max:200'],
        ]);
        $levelId = DB::table('education_levels')->where('code',$data['level_code'])->value('id');
        $m = $e->educations()->create(array_merge($data, ['level_id'=>$levelId]));
        return response()->json($m, 201);
    }

    public function update(Request $r, int $id, int $eduId) {
        $e = Employee::findOrFail($id);
        $m = $e->educations()->findOrFail($eduId);
        $data = $r->validate([
            'level_code'     => ['nullable','string'],
            'school_name'    => ['required','string','max:200'],
            'school_location'=> ['nullable','string','max:200'],
            'date_from'      => ['nullable','date'],
            'date_to'        => ['nullable','date','after_or_equal:date_from'],
            'is_completed'   => ['boolean'],
            'course'         => ['nullable','string','max:200'],
            'honors'         => ['nullable','string','max:200'],
        ]);
        if (!empty($data['level_code'])) {
            $data['level_id'] = DB::table('education_levels')->where('code',$data['level_code'])->value('id');
        }
        unset($data['level_code']);
        $m->fill($data)->save();
        return response()->json($m);
    }

    public function destroy(int $id, int $eduId) {
        Employee::findOrFail($id)->educations()->findOrFail($eduId)->delete();
        return response()->json(['ok'=>true]);
    }
}
