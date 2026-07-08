@extends('layouts.app')

@section('title', 'Production History')
@section('page-title')
    <i class="bi bi-clock-history me-2 text-secondary"></i>
    Production History
    @if($department !== 'all')
        — {{ $departments[$department]['label'] ?? ucfirst($department) }}
    @endif
@endsection

@push('styles')
<style>
    .period-btn.active { font-weight: 700; }
    .hist-table th { font-size: .72rem; font-weight: 700; text-transform: uppercase;
                     letter-spacing: .5px; color: #6c757d; }
    .hist-table td { font-size: .875rem; vertical-align: middle; }
    .dept-badge-design  { background: #cff4fc; color: #055160; }
    .dept-badge-print   { background: #fff3cd; color: #664d03; }
    .dept-badge-sew     { background: #f3e8ff; color: #5b21b6; }
    .chart-bar-wrap { height: 80px; display: flex; align-items: flex-end; gap: 3px; }
    .chart-bar { flex: 1; background: #0d6efd; border-radius: 3px 3px 0 0; min-width: 4px;
                 transition: opacity .15s; cursor: default; }
    .chart-bar:hover { opacity: .75; }
</style>
@endpush

@section('content')

{{-- ── Department tabs (pipeline manager only) ────────────── --}}
@if(auth()->user()->isPipelineManager())
<div class="d-flex gap-2 mb-4 flex-wrap">
    @foreach([
        'all'    => ['label' => 'All Departments', 'icon' => 'bi-grid-3x3-gap'],
        'design' => ['label' => 'Design',           'icon' => 'bi-pencil-square'],
        'print'  => ['label' => 'Printing',         'icon' => 'bi-printer'],
        'sew'    => ['label' => 'Sewing',            'icon' => 'bi-scissors'],
    ] as $dept => $info)
        <a href="{{ route('history.index', ['department' => $dept]) }}?period={{ $period }}"
           class="btn btn-sm {{ $department === $dept ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi {{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
        </a>
    @endforeach
</div>
@endif

{{-- ── Filter bar ──────────────────────────────────────────── --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET"
              action="{{ route('history.index', ['department' => $department]) }}"
              class="d-flex flex-wrap align-items-end gap-3">

            {{-- Preset periods --}}
            <div>
                <label class="form-label mb-1 text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">
                    Quick Range
                </label>
                <div class="btn-group btn-group-sm" role="group">
                    @foreach(['today' => 'Today', 'week' => 'Last 7 Days', 'month' => 'Last 30 Days', 'year' => 'Last 365 Days'] as $key => $label)
                        <a href="{{ route('history.index', ['department' => $department]) }}?period={{ $key }}"
                           class="btn period-btn {{ $period === $key ? 'btn-primary active' : 'btn-outline-secondary' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Custom range --}}
            <div class="d-flex align-items-end gap-2">
                <div>
                    <label class="form-label mb-1 text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">
                        From
                    </label>
                    <input type="date" name="from" class="form-control form-control-sm"
                           value="{{ $period === 'custom' ? $from->toDateString() : '' }}"
                           style="min-width:145px">
                </div>
                <div>
                    <label class="form-label mb-1 text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">
                        To
                    </label>
                    <input type="date" name="to" class="form-control form-control-sm"
                           value="{{ $period === 'custom' ? $to->toDateString() : '' }}"
                           style="min-width:145px">
                </div>
                <button type="submit" class="btn btn-sm btn-secondary">
                    <i class="bi bi-funnel me-1"></i>Apply
                </button>
            </div>

            {{-- Download XLSX --}}
            <a href="{{ route('history.export-xlsx', ['department' => $department]) }}?{{ http_build_query(request()->only('period','from','to')) }}"
               class="btn btn-sm btn-outline-success ms-auto">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download XLSX
            </a>
        </form>
    </div>
</div>

{{-- Date range label --}}
<div class="text-muted mb-3" style="font-size:.82rem">
    <i class="bi bi-calendar-range me-1"></i>
    Showing:
    <strong>{{ $from->format('d M Y') }}</strong>
    —
    <strong>{{ $to->format('d M Y') }}</strong>
</div>

{{-- ── Stat cards ──────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <div class="stat-number text-primary">{{ $stats['total_orders'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">Completed Jobs</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-shirt"></i>
                </div>
                <div>
                    <div class="stat-number text-info">{{ number_format($stats['total_units']) }}</div>
                    <div class="text-muted" style="font-size:.75rem">Total Units</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <div class="stat-number text-success">{{ $stats['on_time'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">On-Time</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-alarm"></i>
                </div>
                <div>
                    <div class="stat-number text-danger">{{ $stats['late'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">Late</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ── Mini activity chart ──────────────────────────────────── --}}
@if($chartData->isNotEmpty())
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold" style="font-size:.875rem">
                <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Completions Over Time
            </span>
            <span class="text-muted" style="font-size:.75rem">
                {{ $chartData->count() }} active day(s) in range
            </span>
        </div>
        @php $maxCount = $chartData->max('count') ?: 1; @endphp
        <div class="chart-bar-wrap" id="hist-chart">
            @foreach($chartData as $date => $day)
                @php $heightPct = round(($day['count'] / $maxCount) * 100); @endphp
                <div class="chart-bar"
                     style="height:{{ $heightPct }}%"
                     title="{{ \Carbon\Carbon::parse($date)->format('d M Y') }}: {{ $day['count'] }} job(s), {{ number_format($day['units']) }} units">
                </div>
            @endforeach
        </div>
        <div class="d-flex justify-content-between mt-1" style="font-size:.7rem;color:#adb5bd">
            <span>{{ $chartData->keys()->first() ? \Carbon\Carbon::parse($chartData->keys()->first())->format('d M') : '' }}</span>
            <span>{{ $chartData->keys()->last()  ? \Carbon\Carbon::parse($chartData->keys()->last())->format('d M') : '' }}</span>
        </div>
    </div>
</div>
@endif

{{-- ── History table ────────────────────────────────────────── --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
        <span class="fw-semibold">
            <i class="bi bi-table me-2 text-secondary"></i>Completed Orders
        </span>
        <span class="badge bg-secondary rounded-pill">{{ $completedSchedules->total() }}</span>
    </div>

    @if($completedSchedules->isEmpty())
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
            No completed work found for the selected period
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 hist-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:1%">#</th>
                        <th>Completed At</th>
                        @if($department === 'all')
                            <th class="text-center">Dept</th>
                        @endif
                        <th>Order</th>
                        <th>Customer</th>
                        <th class="text-center">Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Priority</th>
                        <th class="text-center">Delivery</th>
                        <th class="text-center">OT</th>
                        <th class="text-center">Done By</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($completedSchedules as $i => $schedule)
                        @php
                            $order = $schedule->order;
                            // "Late" means the work was finished on a day AFTER the delivery date.
                            // We compare dates only (not times) so completing at any point on the
                            // delivery day itself is considered on time.
                            $completedDate   = \Carbon\Carbon::parse($schedule->completed_at)->startOfDay();
                            $deliveryDate    = $order->delivery_date->copy()->startOfDay();
                            $completedLate   = $completedDate->gt($deliveryDate);
                            $daysOverdue     = $completedLate ? (int) $deliveryDate->diffInDays($completedDate) : 0;
                        @endphp
                        <tr class="{{ $completedLate ? 'table-danger' : '' }}">

                            <td class="ps-3 text-muted" style="font-size:.78rem">
                                {{ ($completedSchedules->currentPage() - 1) * $completedSchedules->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                <div class="fw-semibold" style="font-size:.82rem">
                                    {{ \Carbon\Carbon::parse($schedule->completed_at)->format('d M Y') }}
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ \Carbon\Carbon::parse($schedule->completed_at)->format('H:i') }}
                                </div>
                            </td>

                            @if($department === 'all')
                                <td class="text-center">
                                    <span class="badge dept-badge-{{ $schedule->department }}"
                                          style="font-size:.72rem">
                                        {{ ucfirst($schedule->department) }}
                                    </span>
                                </td>
                            @endif

                            <td>
                                <a href="{{ route('orders.show', $order->id) }}"
                                   class="fw-semibold text-decoration-none text-dark"
                                   style="font-size:.875rem">
                                    {{ $order->order_number }}
                                </a>
                                @if($order->whatsapp_order_id)
                                    <div class="text-muted" style="font-size:.7rem">
                                        <i class="bi bi-whatsapp text-success me-1"></i>{{ $order->whatsapp_order_id }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div style="font-size:.875rem">{{ $order->customer_name }}</div>
                                @if($order->customer_phone)
                                    <div class="text-muted" style="font-size:.72rem">{{ $order->customer_phone }}</div>
                                @endif
                            </td>

                            <td class="text-center">
                                <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                    {{ $order->product_type_label }}
                                </span>
                            </td>

                            <td class="text-center fw-semibold">
                                {{ number_format($schedule->quantity_scheduled) }}
                                @if($schedule->quantity_scheduled !== $order->quantity)
                                    <div class="text-muted" style="font-size:.7rem">of {{ number_format($order->quantity) }}</div>
                                @endif
                            </td>

                            <td class="text-center">
                                <span class="badge bg-{{ $order->priority_badge }}">
                                    {{ ucfirst($order->priority) }}
                                </span>
                            </td>

                            <td class="text-center">
                                <div style="font-size:.8rem">
                                    {{ $order->delivery_date->format('d M Y') }}
                                </div>
                                @if($completedLate)
                                    <span class="days-chip bg-danger text-white">
                                        {{ $daysOverdue }}d late
                                    </span>
                                @else
                                    <span class="days-chip bg-success text-white">On time</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($schedule->is_overtime)
                                    <span class="badge bg-warning text-dark" style="font-size:.68rem">OT</span>
                                @else
                                    <span class="text-muted" style="font-size:.78rem">—</span>
                                @endif
                            </td>

                            <td class="text-center" style="font-size:.8rem">
                                {{ $schedule->completedByUser->name ?? '—' }}
                            </td>

                            <td class="text-center pe-3">
                                <span class="badge bg-{{ $order->status_badge }}">
                                    {{ ucwords(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2"
             style="font-size:.78rem">
            <span class="text-muted">
                Showing {{ $completedSchedules->firstItem() }}–{{ $completedSchedules->lastItem() }}
                of {{ $completedSchedules->total() }} job(s) ·
                <strong>{{ number_format($stats['total_units']) }}</strong> total units
            </span>
            <div class="d-flex align-items-center gap-3">
                {{ $completedSchedules->links() }}
                <a href="{{ route('history.export-xlsx', ['department' => $department]) }}?{{ http_build_query(request()->only('period','from','to')) }}"
                   class="btn btn-sm btn-outline-success flex-shrink-0">
                    <i class="bi bi-download me-1"></i>Download XLSX
                </a>
            </div>
        </div>
    @endif
</div>

@endsection
