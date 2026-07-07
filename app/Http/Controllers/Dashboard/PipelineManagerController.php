<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Scheduling\SchedulingService;
use Illuminate\View\View;

class PipelineManagerController extends Controller
{
    public function __construct(private readonly SchedulingService $scheduler) {}

    public function index(): View
    {
        $plan = $this->scheduler->buildDailyPlan();

        // Stage counts for the stat cards
        $stats = [
            'total'     => Order::whereNotIn('status', ['cancelled'])->count(),
            'design'    => Order::where('stage', 'design')
                                ->whereNotIn('status', ['cancelled'])->count(),
            'print'     => Order::where('stage', 'print')
                                ->whereNotIn('status', ['cancelled'])->count(),
            'sew'       => Order::where('stage', 'sew')
                                ->whereNotIn('status', ['cancelled'])->count(),
            'ready'     => Order::where('stage', 'ready')
                                ->whereNotIn('status', ['cancelled'])->count(),
            'delivered' => Order::where('stage', 'delivered')->count(),
        ];

        // Upcoming deliveries: active orders due within the next 7 days
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

        return view('dashboard.pipeline', compact('plan', 'stats', 'upcomingOrders'));
    }
}
