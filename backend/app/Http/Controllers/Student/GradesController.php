<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class GradesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'Unauthenticated'], 401);
        }

        // 1) Resolve student_number (enum-safe cast to text)
        $link = DB::table('public.user_identity_links')
            ->select('student_number')
            ->where('user_id', $user->id)
            ->whereRaw("kind::text = 'student'")   // <-- enum column
            ->orderByDesc('id')
            ->first();

        if (!$link || !$link->student_number) {
            return response()->json(['ok' => false, 'error' => 'No linked student number'], 404);
        }
        $sn = $link->student_number;

        // 2) Profile (PUBLIC schema)
        $profile = DB::table('public.students_profile')
            ->where('student_number', $sn)
            ->first();

        // 3) Latest status (PUBLIC schema)
        $status = DB::table('public.students_status_history')
            ->where('student_number', $sn)
            ->orderByDesc('created_at')
            ->orderByDesc('enrolled_date')
            ->first();

        $courseCode = $status->course_code ?? null;
        $yearLevel  = $status->year_level ?? null;

        // 4) Program (PUBLIC first; fallback to student.* if needed)
        $program = null;
        if ($courseCode) {
            try {
                $program = DB::table('public.programs')
                    ->select('code','detail','college')
                    ->where('code', $courseCode)
                    ->first();
            } catch (QueryException $e) {
                try {
                    $program = DB::table('student.programs')
                        ->select('code','detail','college')
                        ->where('code', $courseCode)
                        ->first();
                } catch (\Throwable $e2) {
                    // leave null
                }
            }
        }

        // 5) Class list (STUDENT schema) + subject info (PUBLIC)
        $classes = DB::table('student.class_list as cl')
            ->leftJoin('public.subjects as s', 's.code', '=', 'cl.subject_code')
            ->selectRaw("
                cl.sy,
                cl.sem,
                cl.section_code,
                cl.subject_code,
                s.title as subject_title,
                (s.units)::double precision as units,
                cl.fin_grade as final_grade,
                cl.permit_number
            ")
            ->where('cl.student_number', $sn)
            ->orderByDesc('cl.sy')
            ->orderByDesc('cl.sem')
            ->orderBy('cl.subject_code')
            ->get();

        // 6) Group by term
        $termsMap = [];
        foreach ($classes as $row) {
            $key = trim((string)$row->sy) . '|' . trim((string)$row->sem);
            if (!isset($termsMap[$key])) {
                $termsMap[$key] = [
                    'sy'         => (string)$row->sy,
                    'sem'        => (string)$row->sem,
                    'display'    => 'SY ' . $row->sy . ' (Sem ' . $row->sem . ')',
                    'year_level' => null,
                    'classes'    => [],
                ];
            }
            $termsMap[$key]['classes'][] = [
                'subject_code' => $row->subject_code,
                'title'        => $row->subject_title,
                'units'        => $row->units !== null ? (float)$row->units : null,
                'section_code' => $row->section_code,
                'final_grade'  => $row->final_grade,
                'has_permit'   => !is_null($row->permit_number) && trim((string)$row->permit_number) !== '',
            ];
        }

        // 7) Year level per term from class_list (STUDENT schema)
        $ylByTerm = DB::table('student.class_list')
            ->select('sy','sem','year_level')
            ->where('student_number', $sn)
            ->get();
        foreach ($ylByTerm as $r) {
            $k = trim((string)$r->sy) . '|' . trim((string)$r->sem);
            if (isset($termsMap[$k]) && $termsMap[$k]['year_level'] === null) {
                $termsMap[$k]['year_level'] = $r->year_level;
            }
        }

        // 8) Sort terms DESC
        $terms = array_values($termsMap);
        usort($terms, function ($a, $b) {
            if ($a['sy'] === $b['sy']) {
                return (int)$b['sem'] <=> (int)$a['sem'];
            }
            return strcmp((string)$b['sy'], (string)$a['sy']);
        });

        // 9) Full name
        $fullName = $profile
            ? trim(
                ($profile->last_name ?? '') . ', ' .
                ($profile->first_name ?? '') .
                (isset($profile->middle_name) && $profile->middle_name !== '' ? ' ' . mb_substr($profile->middle_name, 0, 1) . '.' : '') .
                (isset($profile->ext_name) && $profile->ext_name !== '' ? ' ' . $profile->ext_name : '')
            )
            : ($user->name ?? $sn);

        return response()->json([
            'ok'      => true,
            'student' => [
                'student_number' => $sn,
                'full_name'      => $fullName,
                'course_code'    => $courseCode,
                'course_name'    => $program->detail ?? null,
                'college'        => $program->college ?? null,
                'year_level'     => $yearLevel,
            ],
            'terms'   => $terms,
        ]);
    }
}
