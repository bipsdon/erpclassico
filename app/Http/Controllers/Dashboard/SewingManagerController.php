<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Concerns\HasPerfStats;
use App\Models\Order;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SewingManagerController extends Controller
{
    use HasPerfStats;

    public function __construct(private readonly SchedulingService $scheduler) {}

    public function index(): View
    {
        $plan = $this->scheduler->buildDailyPlan();

        $queue         = $plan->sewQueue;
        $tomorrowQueue = $this->scheduler->buildTomorrowQueue('sew');

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

        return view('dashboard.sewing', compact('queue', 'tomorrowQueue', 'upcomingOrders'));
    }

    public function performance(Request $request): View
    {
        [$from, $to, $period] = $this->resolvePerfDateRange($request);

        $perf     = $this->perfStats('sew', $from, $to);
        $perfMine = $this->perfStats('sew', $from, $to, auth()->id());

        return view('dashboard.sewing-performance', compact('perf', 'perfMine', 'from', 'to', 'period'));
    }
}
