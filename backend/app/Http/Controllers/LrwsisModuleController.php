<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LrwsisModuleController extends Controller
{
    /**
     * GET /app/api/lrwsis/modules
     * Middleware: auth:sanctum, app.access:LRWSIS
     *
     * Returns:
     * [
     *   {
     *     system_id: number,            // auto-assigned for UI (stable per request)
     *     system_code: string,          // original system_mains.system_id
     *     system_name: string,
     *     modules: [
     *       {
     *         module_id: number,
     *         module_name: string,
     *         sub_modules: [
     *           { sub_module_id:number, sub_module_name:string, component_path:string|null }
     *         ]
     *       }
     *     ]
     *   }
     * ]
     */
    public function myModules(Request $request)
    {
        $userId = $request->user()->id;

        // If application_users.users_employees_id is actually users.id, this works directly.
        // If not, see the optional view below (vw_user_access) and replace au.* with ua.* as noted.
        $rows = DB::table('application_sub_modules as sm')
            ->join('application_modules as m', 'm.id', '=', 'sm.application_module_id')
            ->join('system_mains as s', 's.system_id', '=', 'm.system_main_id')
            ->join('application_users as au', 'au.application_sub_module_id', '=', 'sm.id')
            ->where('au.users_employees_id', $userId) // <- replace with 'ua.user_id' if using the optional view
            ->select([
                's.system_id as system_code',
                's.system_name',
                's.sort_order as system_sort',
                'm.id as module_id',
                'm.module_name',
                'm.sort_order as module_sort',
                'sm.id as sub_module_id',
                'sm.sub_module_name',
                'sm.component_path',
                'sm.sort_order as sub_sort',
            ])
            ->orderBy('s.sort_order')
            ->orderBy('m.sort_order')
            ->orderBy('sm.sort_order')
            ->get();

        // Shape to your React types and auto-assign numeric system_id for the UI toggle arrays.
        $bySystem = [];
        $systemIndex = 1;                   // auto numeric id for UI
        $systemCodeToId = [];

        foreach ($rows as $r) {
            if (!isset($systemCodeToId[$r->system_code])) {
                $systemCodeToId[$r->system_code] = $systemIndex++;
            }
            $sid = $systemCodeToId[$r->system_code];

            if (!isset($bySystem[$r->system_code])) {
                $bySystem[$r->system_code] = [
                    'system_id'   => $sid,             // numeric for UI
                    'system_code' => $r->system_code,  // original varchar code
                    'system_name' => $r->system_name,
                    'modules'     => [],
                ];
            }

            if (!isset($bySystem[$r->system_code]['modules'][$r->module_id])) {
                $bySystem[$r->system_code]['modules'][$r->module_id] = [
                    'module_id'   => (int)$r->module_id,
                    'module_name' => $r->module_name,
                    'sub_modules' => [],
                ];
            }

            $bySystem[$r->system_code]['modules'][$r->module_id]['sub_modules'][] = [
                'sub_module_id'   => (int)$r->sub_module_id,
                'sub_module_name' => $r->sub_module_name,
                'component_path'  => $r->component_path,
            ];
        }

        // Reindex modules and submodules to plain arrays
        $systems = [];
        foreach ($bySystem as $sys) {
            $sys['modules'] = array_values(array_map(function ($m) {
                $m['sub_modules'] = array_values($m['sub_modules']);
                return $m;
            }, $sys['modules']));
            $systems[] = $sys;
        }

        return response()->json($systems);
    }
}
