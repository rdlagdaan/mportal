<?php

namespace App\Services\HRSI\LeaveManagement;

use App\Models\Holiday;
use App\Models\SchoolYear;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CalendarService
{
    /**
     * Return [startDate, endDateExclusive] for a table that stores DATERANGE in 'period'.
     */
    public function getRange(string $table, int $id): array
    {
        $row = DB::table($table)->selectRaw('lower(period) AS start_date, upper(period) AS end_date')->where('id',$id)->first();
        return [Carbon::parse($row->start_date)->startOfDay(), Carbon::parse($row->end_date)->startOfDay()];
    }

    /**
     * Count working days between start(inclusive) and end(exclusive), skipping weekends and holidays (is_working_day=false).
     */
    public function workingDays(Carbon $start, Carbon $end, int $schoolYearId): int
    {
        if ($end->lte($start)) return 0;

        $weekend = collect(Config::get('leave.weekend_days', [0,6]))->flip();
        // Load overlapping holiday ranges once
        $holidays = Holiday::query()
            ->where('school_year_id', $schoolYearId)
            ->whereRaw("period && daterange(?, ?, '[]')", [$start->toDateString(), $end->toDateString()])
            ->get(['period','is_working_day']);

        // Normalize holidays to concrete date set for quick lookup
        $holidaySet = $this->expandHolidayDates($holidays);

        $count = 0;
        foreach (CarbonPeriod::create($start, $end->copy()->subDay()) as $d) {
            if ($weekend->has((int)$d->format('w'))) continue;
            // if date inside a non-working holiday, skip
            if (isset($holidaySet[$d->toDateString()]) && $holidaySet[$d->toDateString()] === false) continue;
            $count++;
        }
        return $count;
    }

    /**
     * Compute leave units (days) for a request period, subtracting weekends/holidays;
     * supports part-day "AM"/"PM" as 0.5.
     */
    public function leaveUnitsForRequest(int $leaveRequestId, int $schoolYearId, ?string $partDay = null): float
    {
        [$start, $end] = $this->getRange('leave_requests', $leaveRequestId);
        $days = $this->workingDays($start, $end, $schoolYearId);
        if ($partDay && $days > 0) {
            // treat first day as 0.5 unit if part-day; adjust as per your policy
            return max(0.5, $days - 0.5);
        }
        return (float)$days;
    }

    /**
     * Expand holiday ranges into [Y-m-d => is_working_day] map.
     */
    protected function expandHolidayDates(Collection $rows): array
    {
        $out = [];
        foreach ($rows as $h) {
            $r = DB::selectOne("SELECT lower(period) AS s, upper(period) AS e FROM holidays WHERE id = ?", [$h->id]);
            $start = Carbon::parse($r->s)->startOfDay();
            $end   = Carbon::parse($r->e)->startOfDay();
            foreach (CarbonPeriod::create($start, $end->copy()->subDay()) as $d) {
                $out[$d->toDateString()] = (bool)$h->is_working_day;
            }
        }
        return $out;
    }
}
