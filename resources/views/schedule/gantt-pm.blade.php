@extends('layouts.app')

@section('title', 'Production Gantt')
@section('page-title')
    <i class="bi bi-bar-chart-steps me-2 text-primary"></i>Production Gantt
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
        /* col 0 = order label (fixed), cols 1..N = day columns */
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

    /* ── Section / swim-lane header ─────────────────────────── */
    .gantt-lane-header {
        grid-column: 1 / -1;
        background: #f0f4f8;
        border-top: 2px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
        padding: .4rem .75rem;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #495057;
        display: flex;
        align-items: center;
        gap: .4rem;
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

    /* ── Gantt bar ───────────────────────────────────────────── */
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
        cursor: default;
    }

    /* Bar colours by priority */
    .gantt-bar.priority-critical { background: #dc3545; }
    .gantt-bar.priority-rush     { background: #fd7e14; }
    .gantt-bar.priority-normal   { background: #0d6efd; }
    .gantt-bar.overtime          { outline: 2px solid #ffc107; outline-offset: 1px; }

    /* ── Delivery pin ────────────────────────────────────────── */
    .delivery-pin {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #198754;
        z-index: 5;
    }

    .delivery-pin::before {
        content: '▼';
        position: absolute;
        top: 2px;
        left: 50%;
        transform: translateX(-50%);
        font-size: .55rem;
        color: #198754;
        line-height: 1;
    }

    /* ── Empty state ─────────────────────────────────────────── */
    .gantt-empty {
        grid-column: 1 / -1;
        padding: 1rem .75rem;
        font-size: .82rem;
        color: #6c757d;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
    }
</style>
@endpush

@section('content')

{{-- ── Legend + controls ──────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="section-title mb-0">
            <i class="bi bi-bar-chart-steps me-1 text-primary"></i>
            14-Day Production Schedule
            <span class="text-muted fw-normal" style="font-size:.78rem">
                — {{ \Carbon\Carbon::parse($dates[0])->format('d M') }} →
                  {{ \Carbon\Carbon::parse($dates[count($dates)-1])->format('d M Y') }}
            </span>
        </span>
    </div>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        {{-- Legend --}}
        <div class="d-flex align-items-center gap-2" style="font-size:.75rem">
            <span class="gantt-bar priority-critical d-inline-flex position-static" style="width:14px;height:14px;border-radius:3px;flex-shrink:0"></span> Critical
            <span class="gantt-bar priority-rush d-inline-flex position-static" style="width:14px;height:14px;border-radius:3px;flex-shrink:0"></span> Rush
            <span class="gantt-bar priority-normal d-inline-flex position-static" style="width:14px;height:14px;border-radius:3px;flex-shrink:0"></span> Normal
            <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#0d6efd;outline:2px solid #ffc107;outline-offset:1px;flex-shrink:0"></span> Overtime
            <span style="display:inline-block;width:2px;height:14px;background:#198754;flex-shrink:0"></span> Delivery date
        </div>
    </div>
</div>

@php
    $deptMeta = [
        'design' => ['label' => 'Design',    'icon' => 'bi-pencil-square', 'color' => 'text-info'],
        'print'  => ['label' => 'Printing',  'icon' => 'bi-printer',       'color' => 'text-warning'],
        'sew'    => ['label' => 'Sewing',    'icon' => 'bi-scissors',      'color' => 'text-purple'],
    ];

    $today_str = $today->toDateString();
@endphp

<div class="card shadow-sm border-0">
    <div class="gantt-wrap">
        <div class="gantt-grid">

            {{-- ── Date header ────────────────────────────────── --}}
            <div class="gantt-head-label">Order / Ref</div>
            @foreach($dates as $d)
                @php
                    $dt         = \Carbon\Carbon::parse($d);
                    $isToday    = $d === $today_str;
                    $isWeekend  = $dt->isWeekend();
                @endphp
                <div class="gantt-head-cell {{ $isToday ? 'today-col' : '' }}">
                    <div>{{ $dt->format('D') }}</div>
                    <div style="font-size:.65rem;opacity:.8">{{ $dt->format('d M') }}</div>
                    @if($isToday)
                        <div style="font-size:.6rem;background:rgba(255,255,255,.25);border-radius:3px;padding:0 3px;margin-top:1px">TODAY</div>
                    @endif
                </div>
            @endforeach

            {{-- ── Department swim lanes ───────────────────────── --}}
            @foreach($departments as $dept)
                @php $meta = $deptMeta[$dept]; @endphp

                {{-- Swim-lane header --}}
                <div class="gantt-lane-header">
                    <i class="bi {{ $meta['icon'] }} {{ $meta['color'] }}"></i>
                    {{ $meta['label'] }}
                    <span class="badge bg-secondary ms-1" style="font-size:.65rem">
                        {{ count($rows[$dept] ?? []) }} order(s)
                    </span>
                </div>

                {{-- Rows for this department --}}
                @forelse($rows[$dept] ?? [] as $bar)
                    {{-- Label cell --}}
                    <div class="gantt-row-label">
                        <a href="{{ route('orders.show', $bar['orderId']) }}" title="{{ $bar['customerName'] }}">
                            {{ $bar['ref'] }}
                        </a>
                        <div class="sub">{{ $bar['customerName'] }}</div>
                        <div class="mt-1 d-flex gap-1 flex-wrap">
                            <span class="badge bg-{{ match($bar['priority']) { 'critical' => 'danger', 'rush' => 'warning', default => 'secondary' } }}"
                                  style="font-size:.6rem">
                                {{ ucfirst($bar['priority']) }}
                            </span>
                            <span class="badge bg-light text-secondary border" style="font-size:.6rem">
                                {{ $bar['quantity'] }} {{ $bar['productType'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Day cells --}}
                    @foreach($dates as $colIdx => $d)
                        @php
                            $dt        = \Carbon\Carbon::parse($d);
                            $isToday   = $d === $today_str;
                            $isWeekend = $dt->isWeekend();

                            // Does the bar start on this column?
                            $isBarStart = $colIdx === $bar['startOffset'];

                            // Is the delivery date on this column?
                            $deliveryOffset = $bar['deliveryOffset'];
                            $isDelivery     = $colIdx === $deliveryOffset
                                              && $deliveryOffset >= 0
                                              && $deliveryOffset < count($dates);
                        @endphp
                        <div class="gantt-cell {{ $isToday ? 'today-col' : ($isWeekend ? 'weekend-col' : '') }}">

                            {{-- Delivery pin (shown on the delivery date column) --}}
                            @if($isDelivery)
                                <div class="delivery-pin"></div>
                            @endif

                            {{-- Bar starts at startOffset, spans daysSpan columns --}}
                            @if($isBarStart)
                                @php
                                    // Calculate how many columns remain in the window from bar start
                                    $remainingCols = count($dates) - $colIdx;
                                    $visibleSpan   = min($bar['daysSpan'], $remainingCols);

                                    // Convert span to CSS: bar width = (visibleSpan columns * 100%) minus gutters
                                    // We use a CSS custom property trick via inline style calc
                                    $spanPct       = $visibleSpan; // used in JS below
                                @endphp
                                <div class="gantt-bar priority-{{ $bar['priority'] }} {{ $bar['isOvertime'] ? 'overtime' : '' }}"
                                     style="right: auto; width: calc({{ $visibleSpan }} * 100% + {{ ($visibleSpan - 1) }} * 1px - 4px);"
                                     title="{{ $bar['ref'] }} — {{ $bar['customerName'] }} ({{ $bar['quantity'] }} × {{ $bar['productType'] }}) | Delivery: {{ \Carbon\Carbon::parse($bar['deliveryDate'])->format('d M Y') }}{{ $bar['isOvertime'] ? ' ⚠ Overtime' : '' }}">
                                    {{ $bar['ref'] }}
                                    @if($bar['daysLeft'] < 0)
                                        <span style="opacity:.85;margin-left:4px">{{ abs($bar['daysLeft']) }}d LATE</span>
                                    @endif
                                </div>
                            @endif

                        </div>
                    @endforeach

                @empty
                    <div class="gantt-empty text-center text-muted py-3">
                        <i class="bi bi-check-circle text-success me-1"></i>No orders scheduled in this department for the next 14 days.
                    </div>
                @endforelse

            @endforeach

        </div>{{-- /gantt-grid --}}
    </div>{{-- /gantt-wrap --}}
</div>

@endsection
