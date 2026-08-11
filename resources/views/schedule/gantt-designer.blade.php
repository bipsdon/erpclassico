@extends('layouts.app')

@section('title', 'Design Deadline View')
@section('page-title')
    <i class="bi bi-bar-chart-steps me-2 text-info"></i>Design Deadline View
@endsection

@push('styles')
<style>
    /* ── Gantt layout ────────────────────────────────────────── */
    .gantt-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .gantt-grid {
        display: grid;
        grid-template-columns: 220px repeat({{ count($dates) }}, minmax(52px, 1fr));
        min-width: calc(220px + {{ count($dates) }} * 52px);
    }

    /* ── Header row ─────────────────────────────────────────── */
    .gantt-head-cell {
        background: var(--erp-brand-color);
        color: #fff;
        font-size: .72rem;
        font-weight: 600;
        text-align: center;
        padding: .45rem .25rem;
        border-right: 1px solid rgba(255,255,255,.1);
        white-space: nowrap;
    }

    .gantt-head-cell.today-col {
        background: #0d6efd;
    }

    .gantt-head-label {
        background: var(--erp-brand-color);
        color: rgba(255,255,255,.6);
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: .45rem .75rem;
        display: flex;
        align-items: center;
    }

    /* ── Row label ───────────────────────────────────────────── */
    .gantt-row-label {
        padding: .4rem .75rem;
        border-bottom: 1px solid #f0f0f0;
        border-right: 1px solid #dee2e6;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 48px;
    }

    .gantt-row-label a {
        font-size: .8rem;
        font-weight: 600;
        color: #1a3c5e;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .gantt-row-label a:hover { text-decoration: underline; }

    .gantt-row-label .sub {
        font-size: .68rem;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    /* ── Day cells ───────────────────────────────────────────── */
    .gantt-cell {
        border-bottom: 1px solid #f0f0f0;
        border-right: 1px solid #f0f0f0;
        background: #fff;
        position: relative;
        min-height: 48px;
    }

    .gantt-cell.today-col {
        background: #f0f6ff;
        border-right-color: #cce0ff;
    }

    .gantt-cell.weekend-col {
        background: #fafafa;
    }

    /* ── Deadline bar ────────────────────────────────────────── */
    /*
     * The bar represents the remaining time window until delivery.
     * Colour encodes urgency, not priority.
     * Red   = due within 2 days (or overdue)
     * Yellow = due within 5 days
     * Green  = 6+ days
     */
    .gantt-bar {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        height: 22px;
        border-radius: 4px;
        left: 2px;
        right: 2px;
        display: flex;
        align-items: center;
        padding: 0 6px;
        font-size: .68rem;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        opacity: .9;
    }

    .gantt-bar.urgency-danger  { background: #dc3545; }
    .gantt-bar.urgency-warning { background: #fd7e14; }
    .gantt-bar.urgency-success { background: #198754; }

    /* Overdue rows get a subtle red tint on the label */
    .gantt-row-label.overdue {
        background: #fff8f8;
        border-left: 3px solid #dc3545;
    }

    /* ── Delivery pin ────────────────────────────────────────── */
    .delivery-pin {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dc3545;
        z-index: 5;
    }

    .delivery-pin::before {
        content: '▼';
        position: absolute;
        top: 2px;
        left: 50%;
        transform: translateX(-50%);
        font-size: .55rem;
        color: #dc3545;
        line-height: 1;
    }

    /* ── Empty state ─────────────────────────────────────────── */
    .gantt-empty {
        grid-column: 1 / -1;
        padding: 1.5rem .75rem;
        font-size: .82rem;
        color: #6c757d;
        background: #fff;
        text-align: center;
    }
</style>
@endpush

@section('content')

@php
    $today_str  = $today->toDateString();
    $totalOrders = count($rows);
    $overdueCount = collect($rows)->where('isOverdue', true)->count();
    $dueTodayCount = collect($rows)->where('daysLeft', 0)->count();
    $dueThisWeekCount = collect($rows)->filter(fn($r) => $r['daysLeft'] >= 1 && $r['daysLeft'] <= 5)->count();
@endphp

{{-- ── Summary stat cards ──────────────────────────────────── --}}
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
                    <div class="stat-number text-warning">{{ $dueThisWeekCount }}</div>
                    <div class="text-muted" style="font-size:.75rem">Due Within 5 Days</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Legend + title ───────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <span class="section-title mb-0">
        <i class="bi bi-hourglass-split me-1 text-info"></i>
        Design Deadline Map
        <span class="text-muted fw-normal" style="font-size:.78rem">
            — {{ \Carbon\Carbon::parse($dates[0])->format('d M') }} →
              {{ \Carbon\Carbon::parse($dates[count($dates)-1])->format('d M Y') }}
        </span>
    </span>
    <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size:.75rem">
        <span class="gantt-bar urgency-danger d-inline-flex position-static" style="width:14px;height:14px;border-radius:3px;flex-shrink:0;opacity:1"></span> ≤ 2 days / Overdue
        <span class="gantt-bar urgency-warning d-inline-flex position-static" style="width:14px;height:14px;border-radius:3px;flex-shrink:0;opacity:1"></span> 3–5 days
        <span class="gantt-bar urgency-success d-inline-flex position-static" style="width:14px;height:14px;border-radius:3px;flex-shrink:0;opacity:1"></span> 6+ days
        <span style="display:inline-block;width:2px;height:14px;background:#dc3545;flex-shrink:0"></span> Delivery date
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="gantt-wrap">
        <div class="gantt-grid">

            {{-- ── Date header ────────────────────────────────── --}}
            <div class="gantt-head-label">Order / Ref</div>
            @foreach($dates as $d)
                @php
                    $dt      = \Carbon\Carbon::parse($d);
                    $isToday = $d === $today_str;
                @endphp
                <div class="gantt-head-cell {{ $isToday ? 'today-col' : '' }}">
                    <div>{{ $dt->format('D') }}</div>
                    <div style="font-size:.65rem;opacity:.8">{{ $dt->format('d M') }}</div>
                    @if($isToday)
                        <div style="font-size:.6rem;background:rgba(255,255,255,.25);border-radius:3px;padding:0 3px;margin-top:1px">TODAY</div>
                    @endif
                </div>
            @endforeach

            {{-- ── Order rows ──────────────────────────────────── --}}
            @forelse($rows as $bar)
                {{-- Label --}}
                <div class="gantt-row-label {{ $bar['isOverdue'] ? 'overdue' : '' }}">
                    <a href="{{ route('orders.show', $bar['orderId']) }}" title="{{ $bar['customerName'] }}">
                        {{ $bar['ref'] }}
                    </a>
                    <div class="sub">{{ $bar['customerName'] }}</div>
                    <div class="mt-1 d-flex gap-1 flex-wrap">
                        <span class="badge bg-{{ match($bar['priority']) { 'critical' => 'danger', 'rush' => 'warning', default => 'secondary' } }}"
                              style="font-size:.6rem">{{ ucfirst($bar['priority']) }}</span>
                        @if($bar['isOverdue'])
                            <span class="badge bg-danger" style="font-size:.6rem">{{ abs($bar['daysLeft']) }}d LATE</span>
                        @elseif($bar['daysLeft'] === 0)
                            <span class="badge bg-danger" style="font-size:.6rem">Due today</span>
                        @else
                            <span class="badge bg-light text-secondary border" style="font-size:.6rem">{{ $bar['daysLeft'] }}d left</span>
                        @endif
                    </div>
                </div>

                {{-- Day cells --}}
                @foreach($dates as $colIdx => $d)
                    @php
                        $dt         = \Carbon\Carbon::parse($d);
                        $isToday    = $d === $today_str;
                        $isWeekend  = $dt->isWeekend();
                        $isBarStart = $colIdx === $bar['barStart'];
                        $isDelivery = $colIdx === $bar['deliveryOffset']
                                      && $bar['deliveryOffset'] >= 0
                                      && $bar['deliveryOffset'] < count($dates);
                    @endphp
                    <div class="gantt-cell {{ $isToday ? 'today-col' : ($isWeekend ? 'weekend-col' : '') }}">

                        {{-- Delivery pin --}}
                        @if($isDelivery)
                            <div class="delivery-pin"></div>
                        @endif

                        {{-- Deadline bar: starts at col 0 (today), spans barSpan columns --}}
                        @if($isBarStart && $bar['barSpan'] > 0)
                            @php
                                $span    = $bar['barSpan'];
                                $urgency = $bar['colour'];
                                $label   = $bar['isOverdue']
                                    ? $bar['ref'] . ' — ' . abs($bar['daysLeft']) . 'd LATE'
                                    : ($bar['daysLeft'] === 0 ? $bar['ref'] . ' — Due today' : $bar['ref'] . ' — ' . $bar['daysLeft'] . 'd left');
                            @endphp
                            <div class="gantt-bar urgency-{{ $urgency }}"
                                 style="right: auto; width: calc({{ $span }} * 100% + {{ ($span - 1) }} * 1px - 4px);"
                                 title="{{ $bar['ref'] }} — {{ $bar['customerName'] }} | {{ $bar['quantity'] }} × {{ $bar['productType'] }} | Due: {{ \Carbon\Carbon::parse($bar['deliveryDate'])->format('d M Y') }}">
                                {{ $label }}
                            </div>
                        @endif

                    </div>
                @endforeach

            @empty
                <div class="gantt-empty">
                    <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-2"></i>
                    No pending design orders. Queue is clear!
                </div>
            @endforelse

        </div>{{-- /gantt-grid --}}
    </div>{{-- /gantt-wrap --}}
</div>

@endsection
