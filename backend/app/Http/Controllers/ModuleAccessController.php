<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ModuleAccessController extends Controller
{
    public function userModules(): JsonResponse
    {
        $userId = \Illuminate\Support\Facades\Auth::id();

        $data = \DB::table('application_users as au')
            ->join('application_sub_modules as asm', 'au.application_sub_module_id', '=', 'asm.id')
            ->join('application_modules as am', 'asm.application_module_id', '=', 'am.id')
            ->join('system_mains as sm', 'am.system_main_id', '=', 'sm.id')
            ->where('au.users_employees_id', $userId)
            ->orderBy('sm.sort_order')
            ->orderBy('am.sort_order')
            ->orderBy('asm.sort_order')
            ->get([
                'sm.id as system_id',
                'sm.system_name',
                'am.id as module_id',
                'am.module_name',
                'asm.id as sub_module_id',
                'asm.sub_module_name',
                'asm.component_path'
            ]);

        if ($data->isEmpty()) {
            return response()->json([], 204);
        }

        // build hierarchy (your code) ...
        $hierarchy = [];
        foreach ($data as $row) {
            $sid = $row->system_id;
            $mid = $row->module_id;

            $hierarchy[$sid] ??= [
                'system_id' => $sid,
                'system_name' => $row->system_name,
                'modules' => []
            ];
            $hierarchy[$sid]['modules'][$mid] ??= [
                'module_id' => $mid,
                'module_name' => $row->module_name,
                'sub_modules' => []
            ];
            $hierarchy[$sid]['modules'][$mid]['sub_modules'][] = [
                'sub_module_id' => $row->sub_module_id,
                'sub_module_name' => $row->sub_module_name,
                'component_path' => $row->component_path,
            ];
        }

        $result = array_values(array_map(function ($system) {
            $system['modules'] = array_values($system['modules']);
            return $system;
        }, $hierarchy));

        return response()->json($result);
    }

}
