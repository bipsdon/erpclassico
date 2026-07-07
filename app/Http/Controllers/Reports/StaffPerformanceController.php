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
    // Roles that do actual production work
    private const WORKER_ROLES = ['designer', 'printing_manager', 'sewing_manager'];

    // Which department each role works in
    private const ROLE_DEPT = [
        'designer'         => 'design',
        'printing_manager' => 'print',
        'sewing_manager'   => 'sew',
    ];

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        [$from, $to, $period] = $this->resolveDateRange($request);

        // All production workers (active or not — history still exists)
        $workers = User::whereIn('role', self::WORKER_ROLES)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $workerIds = $workers->pluck('id');

        // ── Completions per user in the date range ─────────────────────────
        // production_schedules.completed_by + completed_at → units & orders completed
        $completions = DB::table('production_schedules')
            ->whereIn('completed_by', $workerIds)
            ->whereBetween('completed_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->select(
                'completed_by',
                'department',
                DB::raw('COUNT(*) as orders_completed'),
                DB::raw('SUM(quantity_scheduled) as units_completed'),
            )
            ->groupBy('completed_by', 'department')
            ->get()
            ->groupBy('completed_by');

        // ── Stage starts per user (status → in_progress transitions) ──────
        // order_stage_logs where from_status != in_progress and to_status = in_progress
        $starts = DB::table('order_stage_logs')
            ->whereIn('changed_by', $workerIds)
            ->where('to_status', 'in_progress')
            ->whereBetween('created_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->select('changed_by', DB::raw('COUNT(*) as started_count'))
            ->groupBy('changed_by')
            ->pluck('started_count', 'changed_by');

        // ── Late completions: completed_at > orders.delivery_date ─────────
        $lateCompletions = DB::table('production_schedules')
            ->join('orders', 'production_schedules.order_id', '=', 'orders.id')
            ->whereIn('production_schedules.completed_by', $workerIds)
            ->whereBetween('production_schedules.completed_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->whereColumn('production_schedules.completed_at', '>', 'orders.delivery_date')
            ->select(
                'production_schedules.completed_by',
                DB::raw('COUNT(*) as late_count')
            )
            ->groupBy('production_schedules.completed_by')
            ->pluck('late_count', 'completed_by');

        // ── Overtime slots completed ───────────────────────────────────────
        $overtimeSlots = DB::table('production_schedules')
            ->whereIn('completed_by', $workerIds)
            ->where('is_overtime', true)
            ->whereBetween('completed_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->select('completed_by', DB::raw('COUNT(*) as overtime_count'))
            ->groupBy('completed_by')
            ->pluck('overtime_count', 'completed_by');

        // ── Daily output trend per worker (for sparkline / chart) ─────────
        $dailyTrend = DB::table('production_schedules')
            ->whereIn('completed_by', $workerIds)
            ->whereBetween('completed_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->select(
                'completed_by',
                DB::raw('DATE(completed_at) as day'),
                DB::raw('SUM(quantity_scheduled) as units'),
            )
            ->groupBy('completed_by', 'day')
            ->orderBy('day')
            ->get()
            ->groupBy('completed_by');

        // ── Assemble per-worker stats ──────────────────────────────────────
        $staffStats = $workers->map(function (User $user) use (
            $completions, $starts, $lateCompletions, $overtimeSlots, $dailyTrend
        ) {
            $dept      = self::ROLE_DEPT[$user->role] ?? null;
            $userComps = $completions->get($user->id, collect());

            // Filter to own department only
            $deptComp = $userComps->firstWhere('department', $dept);

            $ordersCompleted = (int) ($deptComp->orders_completed ?? 0);
            $unitsCompleted  = (int) ($deptComp->units_completed  ?? 0);
            $startedCount    = (int) ($starts[$user->id]           ?? 0);
            $lateCount       = (int) ($lateCompletions[$user->id]  ?? 0);
            $overtimeCount   = (int) ($overtimeSlots[$user->id]    ?? 0);

            $onTimeCount  = max(0, $ordersCompleted - $lateCount);
            $onTimePct    = $ordersCompleted > 0
                ? round(($onTimeCount / $ordersCompleted) * 100)
                : null;

            // Daily trend as [date => units] map for Chart.js
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
            ];
        });

        // ── Department-level rollup for the summary bar ────────────────────
        $deptTotals = [];
        foreach (self::ROLE_DEPT as $role => $dept) {
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
                'today' => [now()->startOfDay(),                now()->endOfDay()],
                'week'  => [now()->startOfWeek()->startOfDay(), now()->endOfDay()],
                'month' => [now()->startOfMonth()->startOfDay(),now()->endOfDay()],
                'year'  => [now()->startOfYear()->startOfDay(), now()->endOfDay()],
                default => [now()->startOfMonth()->startOfDay(),now()->endOfDay()],
            };
        }

        return [$from, $to, $period];
    }
}
