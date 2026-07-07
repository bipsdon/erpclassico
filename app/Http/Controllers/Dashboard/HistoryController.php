<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\HistoryExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductionSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HistoryController extends Controller
{
    /**
     * Allowed departments and their display labels/icons.
     */
    private const DEPARTMENTS = [
        'design' => ['label' => 'Design',   'icon' => 'bi-pencil-square', 'color' => 'info'],
        'print'  => ['label' => 'Printing', 'icon' => 'bi-printer',       'color' => 'warning'],
        'sew'    => ['label' => 'Sewing',   'icon' => 'bi-scissors',      'color' => 'purple'],
    ];

    // ──────────────────────────────────────────────
    // Page view
    // ──────────────────────────────────────────────

    public function index(Request $request, string $department = 'all'): View
    {
        $this->authorizeAccess($department);

        [$from, $to, $period] = $this->resolveDateRange($request);

        $completedSchedules = $this->queryCompletedSchedules($department, $from, $to);

        // Summary stats
        $stats = [
            'total_orders'    => $completedSchedules->count(),
            'total_units'     => (int) $completedSchedules->sum('quantity_scheduled'),
            'on_time'         => $completedSchedules->filter(fn ($s) => ! $s->order->is_late)->count(),
            'late'            => $completedSchedules->filter(fn ($s) => $s->order->is_late)->count(),
        ];

        // Chart data — completions grouped by date (last N days)
        $chartData = $completedSchedules
            ->groupBy(fn ($s) => Carbon::parse($s->completed_at)->toDateString())
            ->map(fn ($group) => [
                'count' => $group->count(),
                'units' => $group->sum('quantity_scheduled'),
            ])
            ->sortKeys();

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
    // XLSX download
    // ──────────────────────────────────────────────

    public function exportXlsx(Request $request, string $department = 'all'): BinaryFileResponse
    {
        $this->authorizeAccess($department);

        [$from, $to, $period] = $this->resolveDateRange($request);

        $completedSchedules = $this->queryCompletedSchedules($department, $from, $to);

        $label    = $department === 'all' ? 'All-Departments' : ucfirst($department);
        $filename = "history-{$label}-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";

        return Excel::download(
            new HistoryExport($completedSchedules, $department, $from, $to),
            $filename
        );
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function queryCompletedSchedules(string $department, Carbon $from, Carbon $to)
    {
        $query = ProductionSchedule::with(['order.creator', 'completedByUser'])
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->orderByDesc('completed_at');

        if ($department !== 'all') {
            $query->where('department', $department);
        }

        return $query->get();
    }

    private function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', 'week');

        // Custom date range overrides preset period
        if ($request->filled('from') && $request->filled('to')) {
            $from   = Carbon::parse($request->input('from'))->startOfDay();
            $to     = Carbon::parse($request->input('to'))->endOfDay();
            $period = 'custom';
        } else {
            [$from, $to] = match ($period) {
                'today'  => [now()->startOfDay(), now()->endOfDay()],
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

        // Pipeline manager can see all
        if ($user->isPipelineManager()) {
            return;
        }

        $allowed = match ($user->role) {
            'designer'        => ['design'],
            'printing_manager'=> ['print'],
            'sewing_manager'  => ['sew'],
            default           => [],
        };

        abort_if(
            $department !== 'all' && ! in_array($department, $allowed),
            403,
            'You do not have access to this department history.'
        );

        // Non-managers cannot see the "all" view
        if ($department === 'all') {
            abort_unless($user->isPipelineManager(), 403);
        }
    }
}
