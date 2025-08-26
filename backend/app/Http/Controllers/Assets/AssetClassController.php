<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Events\AssetClassSaved;  
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;



class AssetClassController extends Controller
{
    public function index(Request $request) {
        $user = $request->user();
        $companyId = $user->company_id ?? (int)$request->input('company_id');
        $q = $request->input('q'); $order = $request->input('order','class_code');
        $per = max(1,(int)$request->input('per_page',10));

        $rows = AssetClass::query()->company($companyId)->search($q)->sorted($order)->paginate($per);
        return response()->json($rows);
    }

    public function store(Request $request) {
        $user = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $data = $request->validate([
            'class_code' => [
                'required','string','max:8',
                'regex:/^[A-Z0-9\-]+$/',   // <- letters, digits, dash only
                //Rule::unique('asset_classes','class_code')
                //    ->where(fn($q)=>$q->where('company_id',$companyId)),

                Rule::unique(AssetClass::class, 'class_code')   // ← model, not string
                        ->where(fn($q) => $q->where('company_id', $companyId)),


            ],
            'class_name' => ['required','string','max:150'],
            'default_life_months' => ['required','integer','min:1','max:360'],
            'default_depr_method' => ['required', Rule::in(['straight_line','declining_balance','units_of_production','none'])],
            'residual_rate' => ['required','numeric','min:0','max:100'],
            'is_active' => ['sometimes','boolean'],
            'sort_order' => ['sometimes','integer','min:0','max:100000'],
        ], [
        'class_code.regex' => 'Only A–Z, 0–9 and dash (-) are allowed; no spaces or special characters.',
        ]);

        
        $data['class_code'] = strtoupper(trim($data['class_code']));

        $m = new AssetClass($data);
        $m->company_id = $companyId; $m->user_id = $user?->id; $m->workstation_id = $request->header('X-Workstation-Id') ?? $request->ip();
        
        try {
            $m->save();
        } catch (QueryException $e) {
            $code = $e->getCode(); // PG codes: 23505 (unique), 23514 (check)
            if ($code === '23505') {
                return response()->json([
                    'message' => 'Class code already exists for this company.'
                ], 422);
            }
            if ($code === '23514') {
                return response()->json([
                    'message' => 'Class code may only contain A–Z, 0–9 and "-".'
                ], 422);
            }
            throw $e; // anything else
        }
        
        
        //$m->save();
        
        event(new \App\Events\AssetClassSaved(
            $m->only(['class_code','class_name','is_active']), (int) $companyId
        ));

        return response()->json($m,201);
    }




    public function update(Request $request, int $id)
    {
        $user      = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        // Fetch only within this company
        /** @var AssetClass $m */
        $m = AssetClass::query()->company($companyId)->findOrFail($id);

        // We still require class_code, but treat it as read-only
        $data = $request->validate([
            'class_code'           => ['required','string','max:8','regex:/^[A-Z0-9-]{1,25}$/'],
            'class_name'           => ['required','string','max:150'],
            'default_life_months'  => ['required','integer','min:1','max:360'],
            'default_depr_method'  => ['required', Rule::in(['straight_line','declining_balance','units_of_production','none'])],
            'residual_rate'        => ['required','numeric','min:0','max:100'],
            'is_active'            => ['sometimes','boolean'],
            'sort_order'           => ['sometimes','integer','min:0','max:100000'],
        ], [
            'class_code.regex' => 'Only A–Z, 0–9 and dash (-) are allowed; no spaces or special characters.',
        ]);

        $payloadCode = strtoupper(trim($data['class_code']));

        // Guard: you can’t change the code in edit

        /*if ($payloadCode !== $m->class_code) {
            return response()->json([
                'message' => "Class code can't be changed."
            ], 409);
        }*/

        // Build SET (never include class_code)
        $set = [
            'class_name'          => $data['class_name'],
            'default_life_months' => $data['default_life_months'],
            'default_depr_method' => $data['default_depr_method'],
            'residual_rate'       => $data['residual_rate'],
            'is_active'           => $data['is_active']  ?? $m->is_active,
            'sort_order'          => $data['sort_order'] ?? $m->sort_order,
            'user_id'             => $user?->id,
            'workstation_id'      => $request->header('X-Workstation-Id') ?? $request->ip(),
            'updated_at'          => now(),
        ];

        try {
            // Exactly like your working SQL: WHERE company_id AND class_code
            $affected = DB::table('assets.asset_classes')
                ->where('company_id', $companyId)
                ->where('class_code', $payloadCode)
                ->update($set);

            if ($affected !== 1) {
                return response()->json(['message' => 'Update failed.'], 409);
            }
        } catch (QueryException $e) {
            if ($e->getCode() === '23514') {
                return response()->json(['message' => 'Invalid data.'], 422);
            }
            throw $e;
        }

        // Return fresh row
        $fresh = AssetClass::query()
            ->company($companyId)
            ->where('class_code', $payloadCode)
            ->first();

        return response()->json($fresh);
    }






    public function destroy(Request $request, int $id)
    {
        $user      = $request->user();
        $companyId = $user->company_id ?? (int) $request->input('company_id');

        $m = \App\Models\Assets\AssetClass::query()
            ->company($companyId)
            ->findOrFail($id);

        try {
            // Optional pre-check gives a precise count for the message
            $inUse = \DB::table('assets.asset_categories')
                ->where('company_id', $companyId)
                ->where('class_code', $m->class_code)
                ->count();

            if ($inUse > 0) {
                return response()->json([
                    'message' => "Cannot delete class {$m->class_code} — it is used by {$inUse} categor" . ($inUse === 1 ? 'y' : 'ies') . ".",
                ], 409);
            }

            $m->delete();
            return response()->json(['ok' => true]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Postgres FK violation = 23503
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => "Cannot delete class {$m->class_code} — it is referenced by existing categories.",
                ], 409);
            }
            throw $e;
        }
        return response()->json(['ok' => true]);
    }

}
