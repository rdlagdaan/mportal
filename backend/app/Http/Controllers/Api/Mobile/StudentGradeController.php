<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobile\StudentGrade;
use Illuminate\Support\Facades\Log;

class StudentGradeController extends Controller
{
    /**
     * Fetch grades for a student filtered by school year and semester
     *
     * @param string $student_number
     * @param string $sy
     * @param string $sem
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($student_number, $sy, $sem)
    {
        // Fetch grades
        $grades = StudentGrade::where('student_number', $student_number)
            ->where('sy', $sy)
            ->where('sem', $sem)
            ->orderBy('subject_code', 'asc')
            ->get();

        // Log for debugging
        Log::info("Student Grades for {$student_number}, SY: {$sy}, SEM: {$sem}", $grades->toArray());

        return response()->json([
            'status' => 'success',
            'grades' => $grades,
        ]);
    }
}