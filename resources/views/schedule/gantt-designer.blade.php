@extends('layouts.app')

@section('title', 'Design Deadline View')
@section('page-title')
    <i class="bi bi-bar-chart-steps me-2 text-info"></i>Design Deadline View
@endsection

@push('styles')
<style>
    .gantt-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    .gantt-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
        table-layout: fixed;
    }

    .gantt-table .col-label    { width: 200px; }
    .gantt-table .col-timeline { /* auto */ }

    /* ── Header ─────────────────────────────────────────────── */
    .gantt-table thead th {
        background: var(--erp-brand-color);
        color: #fff;
        font-size: .7rem;
        font-weight: 600;
        padding: 0;
        border: none;
    }

    .gantt-th-label {
        padding: .5rem .75rem;
        color: rgba(255,255,255,.55);
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        vertical-align: middle;
    }

    .gantt-th-timeline { padding: 0; position: relative; }

    .day-headers { display: flex; height: 100%; }

    .day-header-cell {
        flex: 1;
        text-align: center;
        padding: .3rem .1rem;
        border-left: 1px solid rgba(255,255,255,.12);
        font-size: .68rem;
        line-height: 1.3;
    }

    .day-header-cell.today   { background: #0d6efd; }
    .day-header-cell.weekend { background: rgba(0,0,0,.15); }

    /* ── Data rows ───────────────────────────────────────────── */
    .gantt-row td { border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .gantt-row:hover td { background-color: #f8f9ff; }

    .gantt-label-cell {
        padding: .4rem .75rem;
        border-right: 1px solid #dee2e6;
        background: #fff;
        vertical-align: middle;
    }

    .gantt-label-cell.overdue {
        background: #fff8f8;
        border-left: 3px solid #dc3545;
    }

    .gantt-label-cell .order-ref {
        font-size: .8rem;
        font-weight: 600;
        color: #1a3c5e;
        text-decoration: none;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .gantt-label-cell .order-ref:hover { text-decoration: underline; }

    .gantt-label-cell .customer {
        font-size: .68rem;
        color: #6c757d;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .gantt-timeline-cell {
        padding: 4px 0;
        position: relative;
        background: #fff;
    }

    /* Day grid */
    .day-grid {
        position: absolute;
        inset: 0;
        display: flex;
        pointer-events: none;
        z-index: 0;
    }

    .day-grid-col { flex: 1; border-left: 1px solid #f0f0f0; }
    .day-grid-col.today   { background: rgba(13,110,253,.05); border-left-color: #cce0ff; }
    .day-grid-col.weekend { background: rgba(0,0,0,.018); }

    /* Bar track */
    .bar-track {
        position: relative;
        z-index: 1;
        height: 28px;
        margin: 0 2px;
    }

    /*
     * Deadline bar — full width from today to delivery date.
     * Colour = urgency (not priority).
     *   red    = overdue or ≤ 2 days
     *   orange = 3–5 days
     *   green  = 6+ days
     */
    .gantt-bar {
        position: absolute;
        top: 3px;
        height: 22px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        padding: 0 8px;
        font-size: .7rem;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        box-shadow: 0 1px 3px rgba(0,0,0,.18);
        min-width: 6px;
    }

    .gantt-bar.urgency-danger  { background: linear-gradient(90deg,#dc3545,#c82333); }
    .gantt-bar.urgency-warning { background: linear-gradient(90deg,#fd7e14,#e06c00); }
    .gantt-bar.urgency-success { background: linear-gradient(90deg,#198754,#157347); }

    /* Bar text */
    .bar-ref    { flex-shrink: 0; }
    .bar-detail { opacity: .85; margin-left: 4px; overflow: hidden; text-overflow: ellipsis; }

    /* Delivery pin */
    .delivery-marker {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dc3545;
        z-index: 2;
        border-radius: 2px;
    }

    .delivery-marker::after {
        content: '▾';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        font-size: .6rem;
        color: #dc3545;
        line-height: 1;
    }

    /* Empty */
    .gantt-empty td {
        padding: 2rem .75rem;
        text-align: center;
        color: #6c757d;
        font-size: .82rem;
        background: #fff;
    }

    /* Legend dot */
    .legend-dot {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 3px;
        flex-shrink: 0;
        vertical-align: middle;
    }
</style>
@endpush

@section('content')

@php
    $today_str    = $today->toDateString();
    $totalDays    = count($dates);
    $colPct       = 100 / $totalDays;

    $totalOrders      = count($rows);
    $overdueCount     = collect($rows)->where('isOverdue', true)->count();
    $dueTodayCount    = collect($rows)->where('daysLeft', 0)->count();
    $dueWeekCount     = collect($rows)->filter(fn($r) => $r['daysLeft'] >= 1 && $r['daysLeft'] <= 5)->count();
@endphp

{{-- ── Stat cards ───────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-pencil-square"></i></div>
                <div>
                    <div class="stat-number text-info">{{ $totalOrders }}</div>
                    <div class="text-muted" style="font-size:.75rem">Pending Designs</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-number text-danger">{{ $overdueCount }}</div>
                    <div class="text-muted" style="font-size:.75rem">Overdue</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-calendar-x"></i></div>
                <div>
                    <div class="stat-number text-danger">{{ $dueTodayCount }}</div>
                    <div class="text-muted" style="font-size:.75rem">Due Today</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-calendar-week"></i></div>
                <div>
                    <div class="stat-number text-warning">{{ $dueWeekCount }}</div>
                    <div class="text-muted" style="font-size:.75rem">Due Within 5 Days</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Chart header ─────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h6 class="mb-0 fw-semibold">
        Design Deadline Map
        <span class="text-muted fw-normal" style="font-size:.8rem">
            — {{ \Carbon\Carbon::parse($dates[0])->format('d M') }}
            → {{ \Carbon\Carbon::parse($dates[$totalDays-1])->format('d M Y') }}
        </span>
    </h6>
    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.75rem;color:#495057">
        <span><span class="legend-dot me-1" style="background:#dc3545"></span>≤ 2 days / Overdue</span>
        <span><span class="legend-dot me-1" style="background:#fd7e14"></span>3–5 days</span>
        <span><span class="legend-dot me-1" style="background:#198754"></span>6+ days</span>
        <span style="display:inline-flex;align-items:center;gap:4px">
            <span style="display:inline-block;width:2px;height:14px;background:#dc3545;border-radius:2px"></span>Delivery date
        </span>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="gantt-wrap">
        <table class="gantt-table">
            <colgroup>
                <col class="col-label">
                <col class="col-timeline">
            </colgroup>

            <thead>
                <tr>
                    <th class="gantt-th-label">
                        <div class="gantt-th-label">Order / Ref</div>
                    </th>
                    <th class="gantt-th-timeline" style="height:48px">
                        <div class="day-headers" style="height:100%">
                            @foreach($dates as $d)
                                @php
                                    $dt = \Carbon\Carbon::parse($d);
                                    $cls = $d === $today_str ? 'today' : ($dt->isWeekend() ? 'weekend' : '');
                                @endphp
                                <div class="day-header-cell {{ $cls }}">
                                    <div>{{ $dt->format('D') }}</div>
                                    <div style="font-size:.62rem;opacity:.75">{{ $dt->format('d M') }}</div>
                                    @if($d === $today_str)
                                        <div style="font-size:.58rem;background:rgba(255,255,255,.2);border-radius:2px;line-height:1.4">TODAY</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </th>
                </tr>
            </thead>

            <tbody>
            @forelse($rows as $bar)
                @php
                    /*
                     * Bar starts at today (col 0) and spans to delivery date.
                     * left%  = 0 (always starts today)
                     * width% = barSpan / totalDays * 100
                     * Clamp at window edge.
                     */
                    $span     = min($bar['barSpan'], $totalDays - $bar['barStart']);
                    $span     = max(1, $span);
                    $leftPct  = 0.0;
                    $widthPct = round(($span / $totalDays) * 100, 4);
                    $widthPct = max($widthPct, $colPct * 0.9);

                    $delivOffset = $bar['deliveryOffset'];
                    $showPin     = $delivOffset >= 0 && $delivOffset < $totalDays;
                    $pinPct      = round((($delivOffset + 0.5) / $totalDays) * 100, 4);

                    $label = $bar['isOverdue']
                        ? $bar['ref'] . '  ' . abs($bar['daysLeft']) . 'd LATE'
                        : ($bar['daysLeft'] === 0
                            ? $bar['ref'] . '  Due today'
                            : $bar['ref'] . '  ' . $bar['daysLeft'] . 'd left');
                @endphp
                <tr class="gantt-row">
                    <td class="gantt-label-cell {{ $bar['isOverdue'] ? 'overdue' : '' }}">
                        <a href="{{ route('orders.show', $bar['orderId']) }}" class="order-ref">
                            {{ $bar['ref'] }}
                        </a>
                        <span class="customer">{{ $bar['customerName'] }}</span>
                        <div class="mt-1 d-flex gap-1 flex-wrap">
                            <span class="badge bg-{{ match($bar['priority']) {'critical'=>'danger','rush'=>'warning',default=>'secondary'} }}"
                                  style="font-size:.58rem">{{ ucfirst($bar['priority']) }}</span>
                            <span class="badge bg-light text-secondary border" style="font-size:.58rem">
                                {{ $bar['quantity'] }} × {{ $bar['productType'] }}
                            </span>
                            <span class="badge bg-light text-secondary border" style="font-size:.58rem">
                                Due {{ \Carbon\Carbon::parse($bar['deliveryDate'])->format('d M') }}
                            </span>
                            @if($bar['isOverdue'])
                                <span class="badge bg-danger" style="font-size:.58rem">{{ abs($bar['daysLeft']) }}d LATE</span>
                            @elseif($bar['daysLeft'] === 0)
                                <span class="badge bg-danger" style="font-size:.58rem">Due today</span>
                            @else
                                <span class="badge bg-light text-secondary border" style="font-size:.58rem">{{ $bar['daysLeft'] }}d left</span>
                            @endif
                        </div>
                    </td>

                    <td class="gantt-timeline-cell">
                        <div class="day-grid">
                            @foreach($dates as $d)
                                @php $dt = \Carbon\Carbon::parse($d); @endphp
                                <div class="day-grid-col {{ $d === $today_str ? 'today' : ($dt->isWeekend() ? 'weekend' : '') }}"></div>
                            @endforeach
                        </div>

                        <div class="bar-track">
                            @if($showPin)
                                <div class="delivery-marker" style="left:{{ $pinPct }}%"></div>
                            @endif

                            <div class="gantt-bar urgency-{{ $bar['colour'] }}"
                                 style="left:{{ $leftPct }}%; width:{{ $widthPct }}%;"
                                 title="{{ $bar['ref'] }} — {{ $bar['customerName'] }} | {{ $bar['quantity'] }} × {{ $bar['productType'] }} | Delivery: {{ \Carbon\Carbon::parse($bar['deliveryDate'])->format('d M Y') }}">
                                <span class="bar-ref">{{ $bar['ref'] }}</span>
                                <span class="bar-detail">
                                    · {{ $bar['customerName'] }}
                                    · {{ $bar['quantity'] }}×
                                    · Due {{ \Carbon\Carbon::parse($bar['deliveryDate'])->format('d M') }}
                                    @if($bar['isOverdue']) · {{ abs($bar['daysLeft']) }}d LATE
                                    @elseif($bar['daysLeft'] === 0) · Due today
                                    @else · {{ $bar['daysLeft'] }}d left
                                    @endif
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="gantt-empty">
                    <td colspan="2">
                        <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                        No pending design orders. Queue is clear!
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
