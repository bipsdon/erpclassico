<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Scheduling\SchedulingService;
use Illuminate\View\View;

class PrintingManagerController extends Controller
{
    public function __construct(private readonly SchedulingService $scheduler) {}

    public function index(): View
    {
        $plan = $this->scheduler->buildDailyPlan();

        // Printing manager sees only the print queue
        $queue         = $plan->printQueue;
        $tomorrowQueue = $this->scheduler->buildTomorrowQueue('print');

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

        return view('dashboard.printing', compact('queue', 'tomorrowQueue', 'upcomingOrders'));
    }
}
