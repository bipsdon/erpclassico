<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Scheduling\SchedulingService;
use Illuminate\View\View;

class SewingManagerController extends Controller
{
    public function __construct(private readonly SchedulingService $scheduler) {}

    public function index(): View
    {
        $plan = $this->scheduler->buildDailyPlan();

        // Sewing manager sees only the sewing queue
        $queue         = $plan->sewQueue;
        $tomorrowQueue = $this->scheduler->buildTomorrowQueue('sew');

        // Upcoming deliveries
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
}
