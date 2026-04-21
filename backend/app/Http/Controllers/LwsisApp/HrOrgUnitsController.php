<?php

namespace App\Http\Controllers\LwsisApp;

use App\Http\Controllers\Controller;
use App\Models\LwsisApp\Hris\HrOrgUnit;

class HrOrgUnitsController extends Controller
{
    public function getOrgUnits()
    {
        try {
            $orgUnits = HrOrgUnit::where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'college_id', 'unit_type']);

            return response()->json([
                'status' => 'success',
                'offices' => $orgUnits,
            ]);
        } catch (\Throwable $e) {
            \Log::error('getOrgUnits failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}