<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class DepreciationService
{
    /**
     * Compute a monthly schedule.
     * @param array $opt Keys:
     *   method: 'straight_line'|'declining_balance'|'units_of_production'
     *   cost: float (gross_amount)
     *   residual_rate: float % (0..100) OR residual_value (overrides if provided)
     *   life_months: int >=1
     *   start_date: Y-m-d (in_service_date) (first month of depreciation)
     *   db_multiplier: float (default 2.0 for DDB), or db_rate_annual as decimal (e.g. 0.2)
     *   uop_total_units: float (required for UoP)
     *   uop_monthly_units: array<float> length life_months (optional; if absent, spread evenly)
     *   rounding: 2 (decimals)
     */
    public static function compute(array $opt): array
    {
        $method         = $opt['method'] ?? 'straight_line';
        $cost           = max(0.0, (float)($opt['cost'] ?? 0));
        $residualValue  = isset($opt['residual_value']) ? max(0.0, (float)$opt['residual_value']) : null;
        $residualRate   = isset($opt['residual_rate']) ? max(0.0, min(100.0, (float)$opt['residual_rate'])) : null;
        $lifeMonths     = max(1, (int)($opt['life_months'] ?? 1));
        $start          = Carbon::parse($opt['start_date'] ?? now()->startOfMonth())->startOfMonth();
        $round          = (int)($opt['rounding'] ?? 2);

        // derive residual from rate if value not given
        if ($residualValue === null) {
            $residualValue = $residualRate !== null ? round($cost * ($residualRate / 100.0), $round) : 0.0;
        }
        $residualValue = min($residualValue, $cost);
        $deprBase      = max(0.0, $cost - $residualValue);

        $rows = [];
        $accum = 0.0;
        $nbv   = $cost;

        if ($method === 'straight_line') {
            $perMonth = $lifeMonths > 0 ? round($deprBase / $lifeMonths, $round) : 0.0;

            for ($m = 1; $m <= $lifeMonths; $m++) {
                $date = $start->copy()->addMonths($m - 1);
                // last period adjust to hit residual exactly
                $rem = round($deprBase - $accum, $round);
                $dep = ($m === $lifeMonths) ? $rem : min($perMonth, $rem);
                $accum = round($accum + $dep, $round);
                $nbv   = round($cost - $accum, $round);
                $rows[] = [
                    'period' => $m,
                    'period_start' => $date->toDateString(),
                    'depreciation' => $dep,
                    'accumulated'  => $accum,
                    'net_book_value' => $nbv,
                ];
            }
        }
        elseif ($method === 'declining_balance') {
            // Double-declining by default, but allow explicit annual rate.
            $mult      = isset($opt['db_multiplier']) ? (float)$opt['db_multiplier'] : 2.0;
            $rateAnn   = isset($opt['db_rate_annual']) ? (float)$opt['db_rate_annual'] : null;
            $lifeYears = max(0.0833, $lifeMonths / 12.0);
            $annualRate= $rateAnn !== null ? $rateAnn : ($lifeYears > 0 ? ($mult / $lifeYears) : 0);
            $monthlyRate = $annualRate / 12.0; // as decimal, e.g. 0.3333/12

            for ($m = 1; $m <= $lifeMonths; $m++) {
                $date = $start->copy()->addMonths($m - 1);
                // Depreciate NBV but do not cross residual
                $dep = round($nbv * $monthlyRate, $round);
                $maxDep = round(($nbv - $residualValue), $round);
                if ($dep > $maxDep) $dep = $maxDep;
                $accum = round($accum + $dep, $round);
                $nbv   = round($nbv - $dep, $round);
                $rows[] = [
                    'period' => $m,
                    'period_start' => $date->toDateString(),
                    'depreciation' => $dep,
                    'accumulated'  => $accum,
                    'net_book_value' => $nbv,
                ];
                if ($nbv <= $residualValue + pow(10, -$round)) {
                    // Fill remaining periods with zeros if any
                    for ($k = $m + 1; $k <= $lifeMonths; $k++) {
                        $rows[] = [
                            'period' => $k,
                            'period_start' => $start->copy()->addMonths($k - 1)->toDateString(),
                            'depreciation' => 0.0,
                            'accumulated'  => $accum,
                            'net_book_value' => $nbv,
                        ];
                    }
                    break;
                }
            }
        }
        else { // units_of_production
            $totalUnits = max(0.000001, (float)($opt['uop_total_units'] ?? 0.000001));
            /** @var array<int,float>|Collection $perMonths */
            $perMonths = collect($opt['uop_monthly_units'] ?? [])
                ->map(fn($v) => max(0.0, (float)$v))
                ->values();

            if ($perMonths->count() === 0) {
                $even = round($totalUnits / $lifeMonths, 6);
                $perMonths = collect(range(1, $lifeMonths))->map(fn() => $even);
            } else {
                // Normalize length to lifeMonths (pad or trim)
                if ($perMonths->count() < $lifeMonths) {
                    $pad = array_fill(0, $lifeMonths - $perMonths->count(), 0.0);
                    $perMonths = $perMonths->merge($pad);
                } elseif ($perMonths->count() > $lifeMonths) {
                    $perMonths = $perMonths->slice(0, $lifeMonths)->values();
                }
            }

            $ratePerUnit = ($deprBase > 0) ? ($deprBase / $totalUnits) : 0.0;

            for ($m = 1; $m <= $lifeMonths; $m++) {
                $date = $start->copy()->addMonths($m - 1);
                $units = (float)$perMonths[$m - 1];
                $dep = round($units * $ratePerUnit, $round);
                // Clamp to remaining base
                $rem = round($deprBase - $accum, $round);
                if ($dep > $rem) $dep = $rem;

                $accum = round($accum + $dep, $round);
                $nbv   = round($cost - $accum, $round);

                $rows[] = [
                    'period' => $m,
                    'period_start' => $date->toDateString(),
                    'units' => $units,
                    'depreciation' => $dep,
                    'accumulated'  => $accum,
                    'net_book_value' => $nbv,
                ];
                if ($rem <= 0) break;
            }
        }

        return [
            'input' => [
                'method' => $method,
                'cost' => round($cost, $round),
                'residual_value' => round($residualValue, $round),
                'life_months' => $lifeMonths,
                'start_date' => $start->toDateString(),
            ],
            'summary' => [
                'total_depreciation' => round($accum, $round),
                'ending_nbv' => round($nbv, $round),
            ],
            'rows' => $rows,
        ];
    }
}
