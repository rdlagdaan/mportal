<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Mobile\UniversityOffice;

class UniversityOfficeController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'offices' => UniversityOffice::select('office_code', 'office')->orderBy('office_code')->get()
        ]);
    }
}
