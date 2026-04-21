<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\Request;
use App\Models\Mobile\StudentLedger;
use App\Models\Mobile\Payment;
use App\Http\Controllers\Controller;

class StudentLedgerController extends Controller
{
    /**
     * Display all ledger records for a given student.
     *
     * @param  string  $studentNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($studentNumber)
    {
        // Fetch all ledger records for the student
        $ledgers = StudentLedger::where('student_number', $studentNumber)
            ->orderBy('sy', 'asc') // optional: order by school year
            ->get();

        if ($ledgers->isEmpty()) {
            return response()->json([
                'message' => 'No ledger records found for student ' . $studentNumber
            ], 404);
        }

        return response()->json([
            'student_number' => $studentNumber,
            'ledger' => $ledgers
        ]);
    }

    /**
     * Display all payments for a given student's ledger record.
     *
     * @param  string  $studentNumber
     * @param  string  $sy
     * @param  string  $sem
     * @return \Illuminate\Http\JsonResponse
     */
    public function payments($studentNumber, $sy, $sem)
    {
        // Fetch all payments for the student for the specified semester and school year
        $payments = Payment::where('student_number', $studentNumber)
            ->where('sy', $sy)
            ->where('sem', $sem)
            ->orderBy('payment_date', 'asc')
            ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'message' => "No payments found for student {$studentNumber} in {$sem} {$sy}"
            ], 404);
        }

        return response()->json([
            'student_number' => $studentNumber,
            'sy' => $sy,
            'sem' => $sem,
            'payments' => $payments
        ]);
    }
    public function showWithPayments($studentNumber, $sy, $sem)
{
    // Fetch ledger records for the student for the given SY & SEM
    $ledgers = StudentLedger::where('student_number', $studentNumber)
        ->where('sy', $sy)
        ->where('sem', $sem)
        ->get();

    // Fetch all ledger records
    $allLedgers = StudentLedger::all();

    if ($ledgers->isEmpty()) {
        return response()->json([
            'message' => 'No ledger records found for student ' . $studentNumber
        ], 404);
    }

    // Attach payments to each ledger record (filtered)
    $ledgers->transform(function ($ledger) use ($sy, $sem) {
        $ledger->payments = Payment::where('student_number', $ledger->student_number)
            ->where('sy', $sy)
            ->where('sem', $sem)
            ->get();
        return $ledger;
    });

    // Attach payments to all ledger records
    $allLedgers->transform(function ($ledger) {
        $ledger->payments = Payment::where('student_number', $ledger->student_number)
            ->where('sy', $ledger->sy)
            ->where('sem', $ledger->sem)
            ->get();
        return $ledger;
    });

    return response()->json([
        'student_number' => $studentNumber,
        'sy' => $sy,
        'sem' => $sem,
        'ledger' => $ledgers,
        'all_ledgers' => $allLedgers // added full student_ledger table
    ]);
}


}