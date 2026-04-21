<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Mobile\College;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function getColleges()
    {
        $colleges = College::select('college_code', 'name')
            ->orderBy('college_code')
            ->get();

        return response()->json($colleges);
    }
}