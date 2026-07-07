@extends('layouts.app')

@section('title', 'Staff Performance')
@section('page-title')
    <i class="bi bi-bar-chart-line me-2 text-primary"></i>Staff Performance Report
@endsection

@push('styles')
<style>
    .perf-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .dept-badge-design    { background: #cff4fc; color: #0a6678; }
    .dept-badge-print     { background: #fff3cd; color: #856404; }
    .dept-badge-sew       { background: #ede9fe; color: #5b21b6; }
    .trend-canvas { max-height: 48px; }
    .metric-label { font-size: .72rem; color: #6c757d; text-transform: uppercase; letter-spacing: .4px; }
    .metric-value { font-size: 1.4rem; font-weight: 700; line-height: 1.1; }
    .worker-card  { border: none; border-radius: .6rem; transition: box-shadow .15s; }
    .worker-card:hover { box-shadow: 0 .4rem 1rem rgba(0,0,0,.1) !important; }
</style>
@endpush

@section('content')

{{-- ── Date range filter ───────────────────────────────────── --}}
<form method="GET" action="{{ route('reports.staff-performance') }}" class="mb-4" id="filter-form">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                {{-- Quick period buttons --}}
                <div>
                    <div class="metric-label mb-1">Quick Period</div>
                    <div class="btn-group" role="group">
                        @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $key => $label)
                            <button type="submit" name="period" value="{{ $key }}"
                                    class="btn btn-sm {{ $period === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Divider --}}
                <div class="vr d-none d-md-block"></div>

                {{-- Custom range --}}
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <div>
                        <label class="metric-label d-block mb-1">From</label>
                        <input type="date" name="from" id="input-from"
                               class="form-control form-control-sm"
                               value="{{ $from->toDateString() }}">
                    </div>
                    <div>
                        <label class="metric-label d-block mb-1">To</label>
                        <input type="date" name="to" id="input-to"
                               class="form-control form-control-sm"
                               value="{{ $to->toDateString() }}">
                    </div>
                    <button type="submit" class="btn btn-sm {{ $period === 'custom' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="bi bi-funnel me-1"></i>Apply Range
                    </button>
                </div>

                {{-- Active range indicator --}}
                <div class="ms-auto text-end">
                    <div class="metric-label">Active Period</div>
                    <div class="fw-semibold" style="font-size:.85rem">
                        @if($from->isSameDay($to))
                            {{ $from->format('d M Y') }}
                        @else
                            {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
                        @endif
                    </div>
                    <div class="text-muted" style="font-size:.75rem">
                        {{ $from->diffInDays($to) + 1 }} day(s)
                    </div>
                </div>

            </div>
        </div>
    </div>
</form>

{{-- ── Department rollup summary ───────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
        $deptMeta = [
            'design' => ['label' => 'Design',   'icon' => 'bi-pencil-square', 'color' => 'info',    'cls' => 'dept-badge-design'],
            'print'  => ['label' => 'Printing',  'icon' => 'bi-printer',       'color' => 'warning', 'cls' => 'dept-badge-print'],
            'sew'    => ['label' => 'Sewing',    'icon' => 'bi-scissors',      'color' => 'purple',  'cls' => 'dept-badge-sew'],
        ];
    @endphp

    @foreach($deptMeta as $dept => $meta)
        @php $t = $deptTotals[$dept] ?? ['orders' => 0, 'units' => 0, 'late' => 0]; @endphp
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="perf-avatar {{ $meta['cls'] }}">
                            <i class="bi {{ $meta['icon'] }}"></i>
                        </span>
                        <div>
                            <div class="fw-semibold">{{ $meta['label'] }} Dept.</div>
                            <div class="text-muted" style="font-size:.75rem">Department total</div>
                        </div>
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="metric-value text-primary">{{ number_format($t['orders']) }}</div>
                            <div class="metric-label">Orders</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value text-success">{{ number_format($t['units']) }}</div>
                            <div class="metric-label">Units</div>
                        </div>
                        <div class="col-4">
                            <div class="metric-value {{ $t['late'] > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ $t['late'] }}
                            </div>
                            <div class="metric-label">Late</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ── Per-worker cards ────────────────────────────────────── --}}
@php
    $grouped = $staffStats->groupBy('department');
    $deptOrder = ['design', 'print', 'sew'];
@endphp

@foreach($deptOrder as $dept)
    @php $workers = $grouped->get($dept, collect()); @endphp
    @if($workers->isEmpty()) @continue @endif

    <div class="section-title mt-2">
        <i class="bi {{ $deptMeta[$dept]['icon'] }} me-2"></i>
        {{ $deptMeta[$dept]['label'] }} — {{ $workers->count() }} {{ Str::plural('staff member', $workers->count()) }}
    </div>

    <div class="row g-3 mb-4">
        @foreach($workers as $stat)
            @php
                $u          = $stat['user'];
                $initials   = collect(explode(' ', $u->name))->map(fn($p) => strtoupper($p[0]))->take(2)->implode('');
                $trendJson  = json_encode(array_values($stat['daily_trend']));
                $trendLabels = json_encode(array_keys($stat['daily_trend']));
                $canvasId   = 'trend-' . $u->id;
                $onTimePct  = $stat['on_time_pct'];
            @endphp
            <div class="col-12 col-lg-6">
                <div class="card worker-card shadow-sm h-100">
                    <div class="card-body">

                        {{-- Header row --}}
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="perf-avatar {{ $deptMeta[$dept]['cls'] }}">{{ $initials }}</div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $u->name }}</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                        {{ $u->role_label }}
                                    </span>
                                    @if(! $u->is_active)
                                        <span class="badge bg-warning text-dark" style="font-size:.7rem">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            {{-- On-time ring --}}
                            @if($onTimePct !== null)
                                <div class="text-center">
                                    <div class="fw-bold fs-5 {{ $onTimePct >= 80 ? 'text-success' : ($onTimePct >= 60 ? 'text-warning' : 'text-danger') }}">
                                        {{ $onTimePct }}%
                                    </div>
                                    <div class="metric-label">on time</div>
                                </div>
                            @else
                                <div class="text-center text-muted">
                                    <div class="fw-bold fs-5">—</div>
                                    <div class="metric-label">on time</div>
                                </div>
                            @endif
                        </div>

                        {{-- Metrics row --}}
                        <div class="row g-2 text-center mb-3">
                            <div class="col-3">
                                <div class="metric-value text-primary">{{ $stat['orders_completed'] }}</div>
                                <div class="metric-label">Completed</div>
                            </div>
                            <div class="col-3">
                                <div class="metric-value text-success">{{ number_format($stat['units_completed']) }}</div>
                                <div class="metric-label">Units</div>
                            </div>
                            <div class="col-3">
                                <div class="metric-value {{ $stat['late_count'] > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ $stat['late_count'] }}
                                </div>
                                <div class="metric-label">Late</div>
                            </div>
                            <div class="col-3">
                                <div class="metric-value {{ $stat['overtime_count'] > 0 ? 'text-warning' : 'text-muted' }}">
                                    {{ $stat['overtime_count'] }}
                                </div>
                                <div class="metric-label">Overtime</div>
                            </div>
                        </div>

                        {{-- On-time progress bar --}}
                        @if($stat['orders_completed'] > 0)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1" style="font-size:.75rem">
                                    <span class="text-muted">On-time rate</span>
                                    <span class="fw-semibold">{{ $stat['on_time_count'] }} / {{ $stat['orders_completed'] }}</span>
                                </div>
                                <div class="progress" style="height:8px;border-radius:4px">
                                    <div class="progress-bar bg-{{ $onTimePct >= 80 ? 'success' : ($onTimePct >= 60 ? 'warning' : 'danger') }}"
                                         style="width:{{ $onTimePct }}%"></div>
                                </div>
                            </div>
                        @endif

                        {{-- Daily output sparkline --}}
                        @if(count($stat['daily_trend']) > 0)
                            <div>
                                <div class="metric-label mb-1">Daily output (units)</div>
                                <canvas id="{{ $canvasId }}" class="trend-canvas w-100"
                                        data-values="{{ $trendJson }}"
                                        data-labels="{{ $trendLabels }}">
                                </canvas>
                            </div>
                        @else
                            <div class="text-muted text-center py-2" style="font-size:.8rem">
                                No completions in this period
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach

@if($staffStats->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-people fs-2 d-block mb-2"></i>
            No production staff found. Create designer, printing manager, or sewing manager accounts first.
        </div>
    </div>
@endif

@endsection

@push('scripts')
{{-- Chart.js for sparklines --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('canvas[data-values]').forEach(function (canvas) {
        const values = JSON.parse(canvas.dataset.values);
        const labels = JSON.parse(canvas.dataset.labels);

        // Format dates as "d M" for display
        const shortLabels = labels.map(function (d) {
            const date = new Date(d);
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
        });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: shortLabels,
                datasets: [{
                    data: values,
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor:     'rgba(13, 110, 253, 0.8)',
                    borderWidth: 1,
                    borderRadius: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: {
                    callbacks: {
                        title: (items) => items[0].label,
                        label: (item)  => item.raw + ' units',
                    }
                }},
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: true },
                }
            }
        });
    });
});
</script>
@endpush
