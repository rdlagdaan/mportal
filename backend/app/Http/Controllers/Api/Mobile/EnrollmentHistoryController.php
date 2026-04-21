<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobile\EnrollmentHistory;

class EnrollmentHistoryController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Get logged-in user from token
        $user = $request->user();

        // ✅ Extract student_number from identityLinks
        $studentNumber = $user->identityLinks?->first()?->student_number;

        if (!$studentNumber) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No student number linked to this user.',
            ], 404);
        }

        // ✅ Fetch enrollment history for this student
        $enrollmentHistory = EnrollmentHistory::where('student_number', $studentNumber)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Enrollment history retrieved successfully',
            'data'    => $enrollmentHistory,
        ]);
    }
}