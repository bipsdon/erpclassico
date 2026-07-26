<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DeliveryInchargeController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user->isPipelineManager() || $user->isDeliveryIncharge(), 403);

        // Orders ready for delivery — sorted by delivery priority
        $readyOrders = Order::where('stage', 'ready')
            ->whereNotIn('status', ['cancelled'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
            ->orderBy('delivery_date')
            ->get();

        // Recent deliveries (last 30 days) by this user (or all for PM)
        $recentQuery = Order::where('stage', 'delivered')
            ->where('delivery_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('updated_at');

        if ($user->isDeliveryIncharge()) {
            $recentQuery->where('delivered_by', $user->id);
        }

        $recentDeliveries = $recentQuery->limit(20)->get();

        // Stats for the current user (or all for PM)
        $statsQuery = Order::where('stage', 'delivered');
        if ($user->isDeliveryIncharge()) {
            $statsQuery->where('delivered_by', $user->id);
        }

        $totalDelivered   = (clone $statsQuery)->count();
        $deliveredToday   = (clone $statsQuery)
            ->whereDate('updated_at', today())->count();
        $deliveredThisWeek = (clone $statsQuery)
            ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $deliveredThisMonth = (clone $statsQuery)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        return view('dashboard.delivery', compact(
            'readyOrders',
            'recentDeliveries',
            'totalDelivered',
            'deliveredToday',
            'deliveredThisWeek',
            'deliveredThisMonth',
        ));
    }
}
