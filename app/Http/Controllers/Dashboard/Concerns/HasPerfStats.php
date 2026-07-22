<?php

namespace App\Http\Controllers\Dashboard\Concerns;

use App\Models\ProductionSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait HasPerfStats
{
    /**
     * Resolve a [from, to, period] tuple from the request.
     * period is one of: today | week | month | year | custom
     */
    protected function resolvePerfDateRange(Request $request): array
    {
        $period = $request->input('period', 'month');

        if ($request->filled('from') && $request->filled('to') && $period === 'custom') {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to   = Carbon::parse($request->input('to'))->endOfDay();
        } else {
            [$from, $to] = match ($period) {
                'today' => [now()->startOfDay(),                 now()->endOfDay()],
                'week'  => [now()->startOfWeek()->startOfDay(),  now()->endOfDay()],
                'year'  => [now()->startOfYear()->startOfDay(),  now()->endOfDay()],
                default => [now()->startOfMonth()->startOfDay(), now()->endOfDay()],  // 'month'
            };
            $period = ($period === 'today' || $period === 'week' || $period === 'year') ? $period : 'month';
        }

        return [$from, $to, $period];
    }

    /**
     * Build performance stats for a department between two dates.
     * Generates a day-by-day series spanning the full from→to range.
     */
    protected function perfStats(string $dept, Carbon $from, Carbon $to, ?int $userId = null): array
    {
        $base = ProductionSchedule::query()
            ->where('department', $dept)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$from, $to]);

        if ($userId !== null) {
            $base->where('completed_by', $userId);
        }

        $daily = (clone $base)
            ->selectRaw('DATE(completed_at) as day, SUM(quantity_scheduled) as units, MAX(is_overtime) as had_overtime, COUNT(*) as jobs')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Build a label/unit/overtime series for every day in the range
        $labels   = [];
        $units    = [];
        $overtime = [];

        $totalDays = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay());

        // Cap at 366 days to avoid huge arrays
        $totalDays = min($totalDays, 365);

        for ($i = $totalDays; $i >= 0; $i--) {
            $date       = $to->copy()->subDays($i)->format('Y-m-d');
            $labels[]   = $to->copy()->subDays($i)->format('d M');
            $units[]    = (int) ($daily[$date]->units ?? 0);
            $overtime[] = (bool) ($daily[$date]->had_overtime ?? false);
        }

        $totalJobs    = $daily->sum('jobs');
        $totalUnits   = $daily->sum('units');
        $overtimeDays = $daily->filter(fn($d) => $d->had_overtime)->count();
        $activeDays   = $daily->count();

        $byProduct = (clone $base)
            ->join('orders', 'orders.id', '=', 'production_schedules.order_id')
            ->selectRaw('orders.product_type, SUM(production_schedules.quantity_scheduled) as units')
            ->groupBy('orders.product_type')
            ->pluck('units', 'product_type')
            ->toArray();

        $byPriority = (clone $base)
            ->join('orders', 'orders.id', '=', 'production_schedules.order_id')
            ->selectRaw('orders.priority, COUNT(*) as jobs')
            ->groupBy('orders.priority')
            ->pluck('jobs', 'priority')
            ->toArray();

        return compact(
            'labels', 'units', 'overtime',
            'totalJobs', 'totalUnits', 'overtimeDays', 'activeDays',
            'byProduct', 'byPriority'
        );
    }
}
