<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffPerformanceController extends Controller
{
    // Roles that do actual production / delivery work
    private const WORKER_ROLES = ['designer', 'printing_manager', 'sewing_manager', 'delivery_incharge'];

    // Which department key each role maps to
    private const ROLE_DEPT = [
        'designer'          => 'design',
        'printing_manager'  => 'print',
        'sewing_manager'    => 'sew',
        'delivery_incharge' => 'delivery',
    ];

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        [$from, $to, $period] = $this->resolveDateRange($request);

        // All production workers (active or not — history still exists)
        $workers   = User::whereIn('role', self::WORKER_ROLES)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $workerIds         = $workers->pluck('id');
        $deliveryWorkerIds = $workers->where('role', 'delivery_incharge')->pluck('id');
        $prodWorkerIds     = $workers->whereNotIn('role', ['delivery_incharge'])->pluck('id');

        // ── Production completions (production_schedules) ──────────────────
        $completions = DB::table('production_schedules')
            ->whereIn('completed_by', $prodWorkerIds)
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select(
                'completed_by',
                'department',
                DB::raw('COUNT(*) as orders_completed'),
                DB::raw('SUM(quantity_scheduled) as units_completed'),
            )
            ->groupBy('completed_by', 'department')
            ->get()
            ->groupBy('completed_by');

        // ── Stage starts per user ──────────────────────────────────────────
        $starts = DB::table('order_stage_logs')
            ->whereIn('changed_by', $prodWorkerIds)
            ->where('to_status', 'in_progress')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('changed_by', DB::raw('COUNT(*) as started_count'))
            ->groupBy('changed_by')
            ->pluck('started_count', 'changed_by');

        // ── Late production completions ────────────────────────────────────
        $lateCompletions = DB::table('production_schedules')
            ->join('orders', 'production_schedules.order_id', '=', 'orders.id')
            ->whereIn('production_schedules.completed_by', $prodWorkerIds)
            ->whereBetween('production_schedules.completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereColumn('production_schedules.completed_at', '>', 'orders.delivery_date')
            ->select('production_schedules.completed_by', DB::raw('COUNT(*) as late_count'))
            ->groupBy('production_schedules.completed_by')
            ->pluck('late_count', 'completed_by');

        // ── Overtime slots ─────────────────────────────────────────────────
        $overtimeSlots = DB::table('production_schedules')
            ->whereIn('completed_by', $prodWorkerIds)
            ->where('is_overtime', true)
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('completed_by', DB::raw('COUNT(*) as overtime_count'))
            ->groupBy('completed_by')
            ->pluck('overtime_count', 'completed_by');

        // ── Daily output trend (production workers) ────────────────────────
        $dailyTrend = DB::table('production_schedules')
            ->whereIn('completed_by', $prodWorkerIds)
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('completed_by', DB::raw('DATE(completed_at) as day'), DB::raw('SUM(quantity_scheduled) as units'))
            ->groupBy('completed_by', 'day')
            ->orderBy('day')
            ->get()
            ->groupBy('completed_by');

        // ── Delivery Incharge stats (sourced from orders.delivered_by) ─────
        // Total orders delivered per user in the period
        $deliveryStats = DB::table('orders')
            ->whereIn('delivered_by', $deliveryWorkerIds)
            ->where('stage', 'delivered')
            ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select(
                'delivered_by',
                DB::raw('COUNT(*) as orders_delivered'),
                DB::raw('SUM(quantity) as units_delivered'),
            )
            ->groupBy('delivered_by')
            ->get()
            ->keyBy('delivered_by');

        // Late deliveries: delivered after delivery_date
        $lateDeliveries = DB::table('orders')
            ->whereIn('delivered_by', $deliveryWorkerIds)
            ->where('stage', 'delivered')
            ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereColumn('updated_at', '>', 'delivery_date')
            ->select('delivered_by', DB::raw('COUNT(*) as late_count'))
            ->groupBy('delivered_by')
            ->pluck('late_count', 'delivered_by');

        // Daily delivery trend per user
        $dailyDeliveryTrend = DB::table('orders')
            ->whereIn('delivered_by', $deliveryWorkerIds)
            ->where('stage', 'delivered')
            ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('delivered_by', DB::raw('DATE(updated_at) as day'), DB::raw('COUNT(*) as orders'))
            ->groupBy('delivered_by', 'day')
            ->orderBy('day')
            ->get()
            ->groupBy('delivered_by');

        // ── Assemble per-worker stats ──────────────────────────────────────
        $staffStats = $workers->map(function (User $user) use (
            $completions, $starts, $lateCompletions, $overtimeSlots, $dailyTrend,
            $deliveryStats, $lateDeliveries, $dailyDeliveryTrend
        ) {
            $dept = self::ROLE_DEPT[$user->role] ?? null;

            if ($user->isDeliveryIncharge()) {
                // Delivery incharge — stats come from orders table
                $ds              = $deliveryStats->get($user->id);
                $ordersDelivered = (int) ($ds->orders_delivered ?? 0);
                $unitsDelivered  = (int) ($ds->units_delivered  ?? 0);
                $lateCount       = (int) ($lateDeliveries[$user->id] ?? 0);
                $onTimeCount     = max(0, $ordersDelivered - $lateCount);
                $onTimePct       = $ordersDelivered > 0
                    ? round(($onTimeCount / $ordersDelivered) * 100)
                    : null;

                $trend = $dailyDeliveryTrend->get($user->id, collect())
                    ->mapWithKeys(fn ($r) => [$r->day => (int) $r->orders])
                    ->toArray();

                return [
                    'user'             => $user,
                    'department'       => $dept,
                    'orders_completed' => $ordersDelivered,
                    'units_completed'  => $unitsDelivered,
                    'started_count'    => 0,
                    'late_count'       => $lateCount,
                    'on_time_count'    => $onTimeCount,
                    'on_time_pct'      => $onTimePct,
                    'overtime_count'   => 0,
                    'daily_trend'      => $trend,
                    'is_delivery'      => true,
                ];
            }

            // Production worker
            $userComps       = $completions->get($user->id, collect());
            $deptComp        = $userComps->firstWhere('department', $dept);
            $ordersCompleted = (int) ($deptComp->orders_completed ?? 0);
            $unitsCompleted  = (int) ($deptComp->units_completed  ?? 0);
            $startedCount    = (int) ($starts[$user->id]           ?? 0);
            $lateCount       = (int) ($lateCompletions[$user->id]  ?? 0);
            $overtimeCount   = (int) ($overtimeSlots[$user->id]    ?? 0);
            $onTimeCount     = max(0, $ordersCompleted - $lateCount);
            $onTimePct       = $ordersCompleted > 0
                ? round(($onTimeCount / $ordersCompleted) * 100)
                : null;

            $trend = $dailyTrend->get($user->id, collect())
                ->mapWithKeys(fn ($r) => [$r->day => (int) $r->units])
                ->toArray();

            return [
                'user'             => $user,
                'department'       => $dept,
                'orders_completed' => $ordersCompleted,
                'units_completed'  => $unitsCompleted,
                'started_count'    => $startedCount,
                'late_count'       => $lateCount,
                'on_time_count'    => $onTimeCount,
                'on_time_pct'      => $onTimePct,
                'overtime_count'   => $overtimeCount,
                'daily_trend'      => $trend,
                'is_delivery'      => false,
            ];
        });

        // ── Department-level rollup ────────────────────────────────────────
        $deptTotals = [];
        foreach (array_unique(array_values(self::ROLE_DEPT)) as $dept) {
            $deptWorkers = $staffStats->filter(fn ($s) => $s['department'] === $dept);
            $deptTotals[$dept] = [
                'orders' => $deptWorkers->sum('orders_completed'),
                'units'  => $deptWorkers->sum('units_completed'),
                'late'   => $deptWorkers->sum('late_count'),
            ];
        }

        return view('reports.staff-performance', compact(
            'staffStats',
            'deptTotals',
            'from',
            'to',
            'period',
        ));
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', 'month');

        if ($request->filled('from') && $request->filled('to') && ! $request->filled('period')) {
            $from   = Carbon::parse($request->input('from'))->startOfDay();
            $to     = Carbon::parse($request->input('to'))->endOfDay();
            $period = 'custom';
        } else {
            [$from, $to] = match ($period) {
                'today' => [now()->startOfDay(),                 now()->endOfDay()],
                'week'  => [now()->startOfWeek()->startOfDay(),  now()->endOfDay()],
                'month' => [now()->startOfMonth()->startOfDay(), now()->endOfDay()],
                'year'  => [now()->startOfYear()->startOfDay(),  now()->endOfDay()],
                default => [now()->startOfMonth()->startOfDay(), now()->endOfDay()],
            };
        }

        return [$from, $to, $period];
    }
}
