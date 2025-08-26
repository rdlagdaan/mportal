<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFactory;
use App\Models\Assets\Asset;
use App\Models\Assets\DepreciationSchedule;
use App\Models\Assets\DepreciationScheduleLine;
use App\Services\DepreciationService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DepreciationScheduleExport;
use Barryvdh\DomPDF\Facade\Pdf;

class DepreciationController extends Controller
{
    // ... keep your preview() method from Phase 3 ...

    /** POST /app/api/assets/{id}/depreciation/export?format=xlsx|pdf */
    public function export($id, Request $req)
    {
        $asset = Asset::query()->where('id', $id)->first();
        abort_if(!$asset, 404);
        $this->authorize('previewDepreciation', $asset);

        // Reuse same validation/options as preview()
        $data = $req->validate([
            'format'           => 'required|in:xlsx,pdf',
            'method'           => 'nullable|in:straight_line,declining_balance,units_of_production',
            'life_months'      => 'nullable|integer|min:1',
            'residual_rate'    => 'nullable|numeric|min:0|max:100',
            'residual_value'   => 'nullable|numeric|min:0',
            'db_multiplier'    => 'nullable|numeric|min:0',
            'db_rate_annual'   => 'nullable|numeric|min:0|max:1',
            'uop_total_units'  => 'nullable|numeric|min:0',
            'uop_monthly_units'=> 'nullable|array',
            'uop_monthly_units.*'=> 'numeric|min:0',
            'start_date'       => 'nullable|date',
            'rounding'         => 'nullable|integer|min:0|max:6',
        ]);

        $opt = [
            'method'         => $data['method'] ?? $asset->depr_method ?? 'straight_line',
            'cost'           => (float)($asset->gross_amount ?? 0),
            'residual_rate'  => $data['residual_rate'] ?? (float)($asset->residual_rate ?? 0),
            'life_months'    => $data['life_months'] ?? (int)($asset->life_months ?? 60),
            'start_date'     => $data['start_date'] ?? ($asset->in_service_date ?? $asset->purchase_date ?? now()->toDateString()),
            'db_multiplier'  => $data['db_multiplier'] ?? 2.0,
            'db_rate_annual' => $data['db_rate_annual'] ?? null,
            'uop_total_units'=> $data['uop_total_units'] ?? null,
            'uop_monthly_units'=> $data['uop_monthly_units'] ?? null,
            'rounding'       => $data['rounding'] ?? 2,
        ];
        if (array_key_exists('residual_value', $data)) {
            $opt['residual_value'] = (float)$data['residual_value'];
        }
        if (($opt['method'] ?? '') === 'units_of_production' && empty($opt['uop_total_units'])) {
            return response()->json(['message' => 'uop_total_units is required for units_of_production.'], 422);
        }

        $schedule = DepreciationService::compute($opt);
        $filename = 'depreciation-' . ($asset->asset_no ?? ('asset-'.$asset->id)) . '.' . $data['format'];

        $view = ViewFactory::make('exports.depreciation', [
            'asset' => $asset,
            'schedule' => $schedule,
        ]);

        if ($data['format'] === 'xlsx') {
            return Excel::download(new DepreciationScheduleExport($view), $filename);
        }

        $pdf = Pdf::loadHTML($view->render())->setPaper('a4', 'portrait');
        return $pdf->download($filename);
    }

    /**
     * POST /app/api/assets/{id}/depreciation/save
     * Save computed schedule to DB.
     */
    public function save($id, Request $req)
    {
        $asset = Asset::query()->where('id', $id)->first();
        abort_if(!$asset, 404);
        $this->authorize('update', $asset); // saving requires update rights

        $data = $req->validate([
            'name'             => 'nullable|string|max:120',
            'method'           => 'nullable|in:straight_line,declining_balance,units_of_production',
            'life_months'      => 'nullable|integer|min:1',
            'residual_rate'    => 'nullable|numeric|min:0|max:100',
            'residual_value'   => 'nullable|numeric|min:0',
            'db_multiplier'    => 'nullable|numeric|min:0',
            'db_rate_annual'   => 'nullable|numeric|min:0|max:1',
            'uop_total_units'  => 'nullable|numeric|min:0',
            'uop_monthly_units'=> 'nullable|array',
            'uop_monthly_units.*'=> 'numeric|min:0',
            'start_date'       => 'nullable|date',
            'rounding'         => 'nullable|integer|min:0|max:6',
        ]);

        $opt = [
            'method'         => $data['method'] ?? $asset->depr_method ?? 'straight_line',
            'cost'           => (float)($asset->gross_amount ?? 0),
            'residual_rate'  => $data['residual_rate'] ?? (float)($asset->residual_rate ?? 0),
            'life_months'    => $data['life_months'] ?? (int)($asset->life_months ?? 60),
            'start_date'     => $data['start_date'] ?? ($asset->in_service_date ?? $asset->purchase_date ?? now()->toDateString()),
            'db_multiplier'  => $data['db_multiplier'] ?? 2.0,
            'db_rate_annual' => $data['db_rate_annual'] ?? null,
            'uop_total_units'=> $data['uop_total_units'] ?? null,
            'uop_monthly_units'=> $data['uop_monthly_units'] ?? null,
            'rounding'       => $data['rounding'] ?? 2,
        ];
        if (array_key_exists('residual_value', $data)) {
            $opt['residual_value'] = (float)$data['residual_value'];
        }
        if (($opt['method'] ?? '') === 'units_of_production' && empty($opt['uop_total_units'])) {
            return response()->json(['message' => 'uop_total_units is required for units_of_production.'], 422);
        }

        $schedule = DepreciationService::compute($opt);

        $sched = \DB::transaction(function () use ($req, $asset, $schedule, $data, $opt) {
            $sid = DepreciationSchedule::query()->insertGetId([
                'asset_id'          => $asset->id,
                'name'              => $data['name'] ?? null,
                'method'            => $schedule['input']['method'],
                'cost'              => $schedule['input']['cost'],
                'residual_rate'     => $opt['residual_rate'] ?? null,
                'residual_value'    => $schedule['input']['residual_value'],
                'life_months'       => $schedule['input']['life_months'],
                'start_date'        => $schedule['input']['start_date'],
                'options'           => json_encode(collect($opt)->except(['method','cost','residual_rate','life_months','start_date','rounding'])->all()),
                'total_depreciation'=> $schedule['summary']['total_depreciation'],
                'ending_nbv'        => $schedule['summary']['ending_nbv'],
                'created_by'        => $req->user()->id,
            ]);

            $rows = collect($schedule['rows'])->map(function ($r) use ($sid) {
                return [
                    'schedule_id'   => $sid,
                    'period'        => $r['period'],
                    'period_start'  => $r['period_start'],
                    'units'         => $r['units'] ?? null,
                    'depreciation'  => $r['depreciation'],
                    'accumulated'   => $r['accumulated'],
                    'net_book_value'=> $r['net_book_value'],
                ];
            })->all();

            // Chunk insert for large schedules
            foreach (array_chunk($rows, 500) as $chunk) {
                DepreciationScheduleLine::insert($chunk);
            }

            return DepreciationSchedule::with('lines')->find($sid);
        });

        return response()->json($sched, 201);
    }

    /** GET /app/api/assets/{id}/depreciation/schedules?per=&page= */
    public function list($id, Request $req)
    {
        $asset = Asset::find($id);
        abort_if(!$asset, 404);
        $this->authorize('view', $asset);

        $per = max(1, min(100, (int)$req->query('per', 10)));
        $rows = DepreciationSchedule::withCount('lines')
            ->where('asset_id', $id)
            ->orderByDesc('created_at')
            ->paginate($per);

        return response()->json($rows);
    }

    /** GET /app/api/depreciation/schedules/{sid} */
    public function showSchedule($sid)
    {
        $sched = DepreciationSchedule::with(['lines','asset'])->find($sid);
        abort_if(!$sched, 404);
        $this->authorize('view', $sched->asset);
        return response()->json($sched);
    }

    /** DELETE /app/api/depreciation/schedules/{sid} */
    public function deleteSchedule($sid, Request $req)
    {
        $sched = DepreciationSchedule::with('asset')->find($sid);
        abort_if(!$sched, 404);
        $this->authorize('update', $sched->asset);

        $sched->delete(); // cascades to lines
        return response()->json(['ok'=>true]);
    }
}
