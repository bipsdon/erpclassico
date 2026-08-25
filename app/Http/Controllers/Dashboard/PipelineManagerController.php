<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CapacityConfig;
use App\Models\Order;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineManagerController extends Controller
{
    public function __construct(private readonly SchedulingService $scheduler) {}

    public function index(Request $request): View
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

        // ── Pieces by Date tab ────────────────────────────────────────
        // Validate and fall back to today if the date is absent or invalid.
        $selectedDate = $request->input('pieces_date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = now()->toDateString();
        }

        $baseQuery = Order::whereNotIn('status', ['cancelled'])
                          ->where('stage', '!=', 'delivered');

        // Orders whose delivery_date is exactly the selected date.
        $dueOnDate = (clone $baseQuery)
            ->whereDate('delivery_date', $selectedDate)
            ->selectRaw('product_type, SUM(quantity) as total_pieces, COUNT(*) as order_count')
            ->groupBy('product_type')
            ->get()
            ->keyBy('product_type');

        // Late orders (delivery_date strictly before the selected date, not yet
        // delivered) — these carry forward and are added to the selected day's load.
        $lateCarryOver = (clone $baseQuery)
            ->whereDate('delivery_date', '<', $selectedDate)
            ->selectRaw('product_type, SUM(quantity) as total_pieces, COUNT(*) as order_count')
            ->groupBy('product_type')
            ->get()
            ->keyBy('product_type');

        // Detailed late orders for the table (individual rows, ordered by urgency)
        $lateOrders = (clone $baseQuery)
            ->whereDate('delivery_date', '<', $selectedDate)
            ->orderBy('delivery_date')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
            ->get();

        // Detailed orders due exactly on the selected date
        $ordersOnDate = (clone $baseQuery)
            ->whereDate('delivery_date', $selectedDate)
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
            ->orderBy('order_date')
            ->get();

        // Grand totals
        $totalPiecesDue  = $dueOnDate->sum('total_pieces');
        $totalPiecesLate = $lateCarryOver->sum('total_pieces');
        $totalPiecesAll  = $totalPiecesDue + $totalPiecesLate;

        // All known product types so we can render every row even with 0 pieces
        $productTypes = CapacityConfig::productTypes();

        return view('dashboard.pipeline', compact(
            'plan',
            'stats',
            'upcomingOrders',
            'selectedDate',
            'dueOnDate',
            'lateCarryOver',
            'ordersOnDate',
            'lateOrders',
            'totalPiecesDue',
            'totalPiecesLate',
            'totalPiecesAll',
            'productTypes',
        ));
    }
}
