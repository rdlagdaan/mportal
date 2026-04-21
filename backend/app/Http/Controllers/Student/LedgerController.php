<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'Unauthenticated'], 401);
        }

        // 1) Resolve student_number (enum-safe cast)
        $link = DB::table('public.user_identity_links')
            ->select('student_number')
            ->where('user_id', $user->id)
            ->whereRaw("kind::text = 'student'")
            ->orderByDesc('id')
            ->first();

        if (!$link || !$link->student_number) {
            return response()->json(['ok' => false, 'error' => 'No linked student number'], 404);
        }
        $sn = $link->student_number;

        // 2) Profile (PUBLIC)
        $profile = DB::table('public.students_profile')
            ->where('student_number', $sn)
            ->first();

        // 3) Latest status (PUBLIC)
        $status = DB::table('public.students_status_history')
            ->where('student_number', $sn)
            ->orderByDesc('created_at')
            ->orderByDesc('enrolled_date')
            ->first();

        $courseCode = $status->course_code ?? null;
        $yearLevel  = $status->year_level ?? null;

        // 4) Program (PUBLIC, fallback student.programs)
        $program = null;
        if ($courseCode) {
            try {
                $program = DB::table('public.programs')->select('code','detail','college')
                    ->where('code', $courseCode)->first();
            } catch (QueryException $e) {
                try {
                    $program = DB::table('student.programs')->select('code','detail','college')
                        ->where('code', $courseCode)->first();
                } catch (\Throwable $e2) { /* ignore */ }
            }
        }

        // 5) Fees by term (STUDENT)
        $fees = DB::table('student.fees')
            ->select('sy','sem',
                DB::raw('(tuition_fee)::numeric as tuition_fee'),
                DB::raw('(miscellaneous_fee)::numeric as miscellaneous_fee'),
                DB::raw('(other_fee)::numeric as other_fee'),
                DB::raw('(installment_fee)::numeric as installment_fee'),
                DB::raw('(additional_fee)::numeric as additional_fee')
            )
            ->where('student_number', $sn)
            ->get();

        // 6) Payments by term (STUDENT) — sorted oldest→newest for running-balance math
        $payments = DB::table('student.payments')
            ->select(
                'sy','sem','or_number','bir_or_number as bir_number','payment_for',
                'transaction_id','date_paid',
                DB::raw('(amount_paid)::numeric as amount_paid')
            )
            ->where('student_number', $sn)
            ->orderBy('sy')         // oldest year first
            ->orderBy('sem')        // oldest sem first
            ->orderBy('date_paid')  // oldest payment first
            ->orderBy('or_number')
            ->get();

        // 7) Build term map (seed from fees; include terms with payments only too)
        $map = [];

        foreach ($fees as $f) {
            $k = trim((string)$f->sy) . '|' . trim((string)$f->sem);
            if (!isset($map[$k])) {
                $map[$k] = [
                    'sy'   => (string)$f->sy,
                    'sem'  => (string)$f->sem,
                    'display' => 'SY ' . $f->sy . ' (Sem ' . $f->sem . ')',
                    'assessment' => [
                        'tuition_fee'       => (float)$f->tuition_fee,
                        'miscellaneous_fee' => (float)$f->miscellaneous_fee,
                        'other_fee'         => (float)$f->other_fee,
                        'installment_fee'   => (float)$f->installment_fee,
                        'additional_fee'    => (float)$f->additional_fee,
                        'total'             => (float)$f->tuition_fee + (float)$f->miscellaneous_fee +
                                               (float)$f->other_fee + (float)$f->installment_fee +
                                               (float)$f->additional_fee,
                    ],
                    'payments'        => [],
                    'total_paid'      => 0.0,
                    'opening_balance' => 0.0, // carry-in from previous term
                    'starting_balance'=> 0.0, // opening + assessment.total
                    'balance'         => 0.0, // ending
                ];
            } else {
                // accumulate multiple fee rows (if any)
                $a = &$map[$k]['assessment'];
                $a['tuition_fee']       += (float)$f->tuition_fee;
                $a['miscellaneous_fee'] += (float)$f->miscellaneous_fee;
                $a['other_fee']         += (float)$f->other_fee;
                $a['installment_fee']   += (float)$f->installment_fee;
                $a['additional_fee']    += (float)$f->additional_fee;
                $a['total']              = $a['tuition_fee'] + $a['miscellaneous_fee'] + $a['other_fee']
                                           + $a['installment_fee'] + $a['additional_fee'];
                unset($a);
            }
        }

        foreach ($payments as $p) {
            $k = trim((string)$p->sy) . '|' . trim((string)$p->sem);
            if (!isset($map[$k])) {
                $map[$k] = [
                    'sy'   => (string)$p->sy,
                    'sem'  => (string)$p->sem,
                    'display' => 'SY ' . $p->sy . ' (Sem ' . $p->sem . ')',
                    'assessment' => [
                        'tuition_fee'       => 0.0,
                        'miscellaneous_fee' => 0.0,
                        'other_fee'         => 0.0,
                        'installment_fee'   => 0.0,
                        'additional_fee'    => 0.0,
                        'total'             => 0.0,
                    ],
                    'payments'        => [],
                    'total_paid'      => 0.0,
                    'opening_balance' => 0.0,
                    'starting_balance'=> 0.0,
                    'balance'         => 0.0,
                ];
            }
            $map[$k]['payments'][] = [
                'or_number'      => $p->or_number,
                'bir_number'     => $p->bir_number,
                'payment_for'    => $p->payment_for,
                'transaction_id' => $p->transaction_id,
                'date_paid'      => optional($p->date_paid)->toAtomString(),
                'amount_paid'    => (float)$p->amount_paid,
                'running_balance'=> null, // to be filled later
            ];
            $map[$k]['total_paid'] += (float)$p->amount_paid;
        }

        // 8) Sort terms ASC (oldest → newest) for cross-term running balance
        $terms = array_values($map);
        usort($terms, function ($a, $b) {
            if ($a['sy'] === $b['sy']) {
                return (int)$a['sem'] <=> (int)$b['sem'];
            }
            return strcmp((string)$a['sy'], (string)$b['sy']);
        });

        // 9) Compute per-term opening/starting/ending and per-payment running balance
        $carryForward = 0.0; // ending balance of previous term
        foreach ($terms as &$term) {
            $assessmentTotal = $term['assessment']['total'];
            $term['opening_balance']  = $carryForward;
            $term['starting_balance'] = $carryForward + $assessmentTotal;

            $running = $term['starting_balance'];
            foreach ($term['payments'] as &$pay) {
                $running -= $pay['amount_paid'];
                $pay['running_balance'] = $running;
            }
            unset($pay);

            $term['balance'] = $running;      // ending for this term
            $carryForward     = $running;     // becomes opening of next term
        }
        unset($term);

        // 10) Build student header
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
            'terms'         => $terms,         // ASC order
            'final_balance' => $carryForward,  // ending balance after last term
        ]);
    }
}
