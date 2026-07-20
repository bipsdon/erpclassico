<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductionSchedule;
use App\Services\Scheduling\SchedulingService;
use Illuminate\View\View;

class DesignerController extends Controller
{
    public function __construct(private readonly SchedulingService $scheduler) {}

    public function index(): View
    {
        $plan  = $this->scheduler->buildDailyPlan();
        $queue = $plan->designQueue;

        $upcomingOrders = Order::with([])
            ->whereNotIn('status', ['cancelled'])
            ->whereNotIn('stage', ['delivered'])
            ->whereBetween('delivery_date', [
                now()->toDateString(),
                now()->addDays(7)->toDateString(),
            ])
            ->orderBy('delivery_date')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
            ->limit(20)
            ->get();

        $perf     = $this->perfStats('design');
        $perfMine = $this->perfStats('design', auth()->id());

        return view('dashboard.designer', compact('queue', 'upcomingOrders', 'perf', 'perfMine'));
    }

    private function perfStats(string $dept, ?int $userId = null): array
    {
        $from = now()->subDays(29)->startOfDay();

        $base = ProductionSchedule::query()
            ->where('department', $dept)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $from);

        if ($userId !== null) {
            $base->where('completed_by', $userId);
        }

        $daily = (clone $base)
            ->selectRaw('DATE(completed_at) as day, SUM(quantity_scheduled) as units, MAX(is_overtime) as had_overtime, COUNT(*) as jobs')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels   = [];
        $units    = [];
        $overtime = [];
        for ($i = 29; $i >= 0; $i--) {
            $date       = now()->subDays($i)->format('Y-m-d');
            $labels[]   = now()->subDays($i)->format('d M');
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
