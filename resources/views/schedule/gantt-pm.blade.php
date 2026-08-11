@extends('layouts.app')

@section('title', 'Production Gantt')
@section('page-title')
    <i class="bi bi-bar-chart-steps me-2 text-primary"></i>Production Gantt
@endsection

@push('styles')
<style>
    .gantt-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    /* ── Outer table ─────────────────────────────────────────── */
    .gantt-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
        table-layout: fixed;
    }

    /* Label column fixed width, timeline takes the rest */
    .gantt-table .col-label { width: 200px; }
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

    /* The timeline header cell holds 14 equal day markers */
    .gantt-th-timeline {
        padding: 0;
        position: relative;
    }

    .day-headers {
        display: flex;
        height: 100%;
    }

    .day-header-cell {
        flex: 1;
        text-align: center;
        padding: .3rem .1rem;
        border-left: 1px solid rgba(255,255,255,.12);
        font-size: .68rem;
        line-height: 1.3;
    }

    .day-header-cell.today { background: #0d6efd; }
    .day-header-cell.weekend { background: rgba(0,0,0,.15); }

    /* ── Swim-lane header row ───────────────────────────────── */
    .lane-header td {
        background: #eef1f5;
        border-top: 2px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
        padding: .35rem .75rem;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #495057;
    }

    /* ── Data rows ───────────────────────────────────────────── */
    .gantt-row td {
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .gantt-row:hover td { background-color: #f8f9ff; }

    /* Label cell */
    .gantt-label-cell {
        padding: .4rem .75rem;
        border-right: 1px solid #dee2e6;
        background: #fff;
        vertical-align: middle;
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
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    /* Timeline cell: holds the bar track + day-grid overlay */
    .gantt-timeline-cell {
        padding: 4px 0;
        position: relative;
        background: #fff;
    }

    /* Day grid lines overlaid on the timeline cell */
    .day-grid {
        position: absolute;
        inset: 0;
        display: flex;
        pointer-events: none;
        z-index: 0;
    }

    .day-grid-col {
        flex: 1;
        border-left: 1px solid #f0f0f0;
    }

    .day-grid-col.today { background: rgba(13,110,253,.05); border-left-color: #cce0ff; }
    .day-grid-col.weekend { background: rgba(0,0,0,.018); }

    /* Bar track: 100% of timeline cell width, relative so bars are positioned inside */
    .bar-track {
        position: relative;
        z-index: 1;
        height: 28px;
        margin: 0 2px;
    }

    /* The actual Gantt bar */
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
        cursor: default;
        min-width: 6px;
    }

    .gantt-bar.priority-critical { background: linear-gradient(90deg,#dc3545,#c82333); }
    .gantt-bar.priority-rush     { background: linear-gradient(90deg,#fd7e14,#e06c00); }
    .gantt-bar.priority-normal   { background: linear-gradient(90deg,#0d6efd,#0a58ca); }
    .gantt-bar.overtime          { box-shadow: 0 0 0 2px #ffc107, 0 1px 3px rgba(0,0,0,.18); }

    /* Bar text: ref always visible, detail fades when bar is narrow */
    .bar-ref    { flex-shrink: 0; }
    .bar-detail { opacity: .85; margin-left: 4px; overflow: hidden; text-overflow: ellipsis; }

    /* Delivery marker: thin vertical line at delivery date */
    .delivery-marker {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #198754;
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
        color: #198754;
        line-height: 1;
    }

    /* ── Empty lane ─────────────────────────────────────────── */
    .lane-empty td {
        padding: .6rem .75rem;
        font-size: .78rem;
        color: #adb5bd;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
    }

    /* ── Legend ─────────────────────────────────────────────── */
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
    $today_str  = $today->toDateString();
    $totalDays  = count($dates);
    $colPct     = 100 / $totalDays;   // % width of one day column

    $deptMeta = [
        'design' => ['label'=>'Design',   'icon'=>'bi-pencil-square', 'color'=>'text-info'],
        'print'  => ['label'=>'Printing', 'icon'=>'bi-printer',       'color'=>'text-warning'],
        'sew'    => ['label'=>'Sewing',   'icon'=>'bi-scissors',      'color'=>''],
    ];
@endphp

{{-- ── Page header ─────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h6 class="mb-0 fw-semibold">
            14-Day Production Schedule
            <span class="text-muted fw-normal" style="font-size:.8rem">
                — {{ \Carbon\Carbon::parse($dates[0])->format('d M') }}
                → {{ \Carbon\Carbon::parse($dates[$totalDays-1])->format('d M Y') }}
            </span>
        </h6>
    </div>
    {{-- Legend --}}
    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.75rem;color:#495057">
        <span><span class="legend-dot me-1" style="background:#dc3545"></span>Critical</span>
        <span><span class="legend-dot me-1" style="background:#fd7e14"></span>Rush</span>
        <span><span class="legend-dot me-1" style="background:#0d6efd"></span>Normal</span>
        <span><span class="legend-dot me-1" style="background:#0d6efd;box-shadow:0 0 0 2px #ffc107"></span>Overtime</span>
        <span style="display:inline-flex;align-items:center;gap:4px">
            <span style="display:inline-block;width:2px;height:14px;background:#198754;border-radius:2px"></span>Delivery date
        </span>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="gantt-wrap">
        <table class="gantt-table">

            {{-- ── Column widths ───────────────────────────── --}}
            <colgroup>
                <col class="col-label">
                <col class="col-timeline">
            </colgroup>

            {{-- ── Date header ─────────────────────────────── --}}
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
            @foreach($departments as $dept)
                @php $meta = $deptMeta[$dept]; @endphp

                {{-- Swim-lane header --}}
                <tr class="lane-header">
                    <td colspan="2">
                        <i class="bi {{ $meta['icon'] }} {{ $meta['color'] }} me-2"></i>
                        {{ $meta['label'] }}
                        <span class="badge bg-secondary ms-1" style="font-size:.62rem">
                            {{ count($rows[$dept] ?? []) }} {{ Str::plural('order', count($rows[$dept] ?? [])) }}
                        </span>
                    </td>
                </tr>

                @forelse($rows[$dept] ?? [] as $bar)
                @php
                    /*
                     * Bar positioning:
                     *   left%  = startOffset / totalDays * 100
                     *   width% = daysSpan    / totalDays * 100
                     *   Clamp so bar never goes past the right edge.
                     */
                    $leftPct     = round(($bar['startOffset'] / $totalDays) * 100, 4);
                    $rawSpan     = min($bar['daysSpan'], $totalDays - $bar['startOffset']);
                    $widthPct    = round(($rawSpan / $totalDays) * 100, 4);
                    $widthPct    = max($widthPct, $colPct * 0.9); // min 90% of one column

                    /*
                     * Delivery marker:
                     *   centre of deliveryOffset column
                     *   = (deliveryOffset + 0.5) / totalDays * 100
                     */
                    $delivOffset = $bar['deliveryOffset'];
                    $showPin     = $delivOffset >= 0 && $delivOffset < $totalDays;
                    $pinPct      = round((($delivOffset + 0.5) / $totalDays) * 100, 4);

                    $priorityClass = 'priority-' . $bar['priority'];
                    $overtimeClass = $bar['isOvertime'] ? 'overtime' : '';

                    $barLabel = $bar['ref'];
                    if ($bar['daysLeft'] < 0) {
                        $barLabel .= '  ' . abs($bar['daysLeft']) . 'd LATE';
                    }
                @endphp
                <tr class="gantt-row">
                    {{-- Label --}}
                    <td class="gantt-label-cell">
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
                            @if($bar['daysLeft'] < 0)
                                <span class="badge bg-danger" style="font-size:.58rem">{{ abs($bar['daysLeft']) }}d LATE</span>
                            @endif
                        </div>
                    </td>

                    {{-- Timeline --}}
                    <td class="gantt-timeline-cell">
                        {{-- Day grid lines --}}
                        <div class="day-grid">
                            @foreach($dates as $d)
                                @php $dt = \Carbon\Carbon::parse($d); @endphp
                                <div class="day-grid-col {{ $d === $today_str ? 'today' : ($dt->isWeekend() ? 'weekend' : '') }}"></div>
                            @endforeach
                        </div>

                        {{-- Bar track --}}
                        <div class="bar-track">

                            {{-- Delivery marker --}}
                            @if($showPin)
                                <div class="delivery-marker" style="left:{{ $pinPct }}%"></div>
                            @endif

                            {{-- Bar --}}
                            <div class="gantt-bar {{ $priorityClass }} {{ $overtimeClass }}"
                                 style="left:{{ $leftPct }}%; width:{{ $widthPct }}%;"
                                 title="{{ $bar['ref'] }} — {{ $bar['customerName'] }} | {{ $bar['quantity'] }} × {{ $bar['productType'] }} | Scheduled: {{ \Carbon\Carbon::parse($bar['scheduledDate'])->format('d M') }} | Delivery: {{ \Carbon\Carbon::parse($bar['deliveryDate'])->format('d M Y') }}{{ $bar['isOvertime'] ? ' ⚠ Overtime' : '' }}{{ $bar['daysLeft'] < 0 ? ' 🔴 LATE' : '' }}">
                                <span class="bar-ref">{{ $bar['ref'] }}</span>
                                <span class="bar-detail">
                                    · {{ $bar['customerName'] }}
                                    · {{ $bar['quantity'] }}×
                                    · Due {{ \Carbon\Carbon::parse($bar['deliveryDate'])->format('d M') }}
                                    @if($bar['daysLeft'] < 0) · {{ abs($bar['daysLeft']) }}d LATE @endif
                                    @if($bar['isOvertime']) · ⚠ OT @endif
                                </span>
                            </div>

                        </div>
                    </td>
                </tr>
                @empty
                <tr class="lane-empty">
                    <td colspan="2">
                        <i class="bi bi-check-circle text-success me-1"></i>
                        No orders scheduled in the next 14 days.
                    </td>
                </tr>
                @endforelse

            @endforeach
            </tbody>
        </table>
    </div>{{-- /gantt-wrap --}}
</div>

@endsection
