<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\HistoryExport;
use App\Http\Controllers\Controller;
use App\Models\ProductionSchedule;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HistoryController extends Controller
{
    private const DEPARTMENTS = [
        'design' => ['label' => 'Design',   'icon' => 'bi-pencil-square', 'color' => 'info'],
        'print'  => ['label' => 'Printing', 'icon' => 'bi-printer',       'color' => 'warning'],
        'sew'    => ['label' => 'Sewing',   'icon' => 'bi-scissors',      'color' => 'purple'],
    ];

    private const PER_PAGE = 25;

    // ──────────────────────────────────────────────
    // Page view  (paginated)
    // ──────────────────────────────────────────────

    public function index(Request $request, string $department = 'all'): View
    {
        $this->authorizeAccess($department);

        [$from, $to, $period] = $this->resolveDateRange($request);

        // Paginated rows for the table
        $completedSchedules = $this->buildQuery($department, $from, $to)
            ->with(['order.creator', 'completedByUser'])
            ->orderByDesc('completed_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Aggregate stats — computed in SQL, not in PHP, so memory stays flat
        $stats = $this->aggregateStats($department, $from, $to);

        // Chart data — daily completion counts (for the full range, not just the page)
        $chartData = $this->buildQuery($department, $from, $to)
            ->select(
                DB::raw("DATE(completed_at) as day"),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(quantity_scheduled) as units')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(fn ($r) => [
                $r->day => ['count' => (int) $r->cnt, 'units' => (int) $r->units]
            ]);

        $departments = self::DEPARTMENTS;

        return view('dashboard.history', compact(
            'completedSchedules',
            'stats',
            'chartData',
            'from',
            'to',
            'period',
            'department',
            'departments',
        ));
    }

    // ──────────────────────────────────────────────
    // XLSX download  (full result, no pagination)
    // ──────────────────────────────────────────────

    public function exportXlsx(Request $request, string $department = 'all'): BinaryFileResponse
    {
        $this->authorizeAccess($department);

        [$from, $to] = $this->resolveDateRange($request);

        $all = $this->buildQuery($department, $from, $to)
            ->with(['order.creator', 'completedByUser'])
            ->orderByDesc('completed_at')
            ->get();

        $label    = $department === 'all' ? 'All-Departments' : ucfirst($department);
        $filename = "history-{$label}-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";

        return Excel::download(
            new HistoryExport($all, $department, $from, $to),
            $filename
        );
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /** Base query — filters only, no eager loads, no ordering. */
    private function buildQuery(string $department, Carbon $from, Carbon $to)
    {
        $query = ProductionSchedule::whereNotNull('completed_at')
            ->whereBetween('completed_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);

        if ($department !== 'all') {
            $query->where('department', $department);
        }

        return $query;
    }

    /** SQL-level aggregates so we never load all rows for the stat cards. */
    private function aggregateStats(string $department, Carbon $from, Carbon $to): array
    {
        $base = $this->buildQuery($department, $from, $to);

        $totals = (clone $base)
            ->selectRaw('COUNT(*) as total, SUM(quantity_scheduled) as units')
            ->first();

        // "Late" = completed on a day AFTER the order's delivery_date.
        // Using DATE(completed_at) > delivery_date so that completing at any
        // time on the delivery day itself counts as on time.
        $lateCount = (clone $base)
            ->join('orders', 'production_schedules.order_id', '=', 'orders.id')
            ->whereRaw('DATE(production_schedules.completed_at) > orders.delivery_date')
            ->count();

        $total = (int) ($totals->total ?? 0);
        $units = (int) ($totals->units ?? 0);

        return [
            'total_orders' => $total,
            'total_units'  => $units,
            'on_time'      => $total - $lateCount,
            'late'         => $lateCount,
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', 'week');

        if ($request->filled('from') && $request->filled('to')) {
            $from   = Carbon::parse($request->input('from'))->startOfDay();
            $to     = Carbon::parse($request->input('to'))->endOfDay();
            $period = 'custom';
        } else {
            [$from, $to] = match ($period) {
                'today'  => [now()->startOfDay(),          now()->endOfDay()],
                'week'   => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
                'month'  => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
                'year'   => [now()->subDays(364)->startOfDay(), now()->endOfDay()],
                default  => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            };
        }

        return [$from, $to, $period];
    }

    private function authorizeAccess(string $department): void
    {
        $user = auth()->user();

        if ($user->isPipelineManager()) {
            return;
        }

        $allowed = match ($user->role) {
            'designer'         => ['design'],
            'printing_manager' => ['print'],
            'sewing_manager'   => ['sew'],
            default            => [],
        };

        // Non-managers may only view their own department, never "all"
        abort_if($department === 'all', 403, 'Access denied.');
        abort_if(! in_array($department, $allowed, true), 403, 'Access denied.');
    }
}
