@extends('layouts.app')

@section('title', 'Design Deadline View')
@section('page-title')
    <i class="bi bi-bar-chart-steps me-2"></i>Design Deadline Map
@endsection

@push('styles')
<style>
:root { --ds-accent: #6366f1; --ds-card-r: .75rem; }
body.role-designer #main-content { background: #f0f0ff; }

.ds-tabs { border-bottom: none; gap: .5rem; }
.ds-tabs .nav-link {
    border: 1.5px solid #d1d5db; border-radius: 2rem !important;
    color: #4b5563; padding: .4rem 1.1rem;
    font-size: .82rem; font-weight: 600; background: #fff; transition: all .2s;
}
.ds-tabs .nav-link.active,
.ds-tabs .nav-link:hover { background: var(--ds-accent); border-color: var(--ds-accent); color: #fff; }

#ds-clock { font-size: .78rem; color: #6b7280; font-variant-numeric: tabular-nums; }

/* Override stat cards to use ds-stat style */
.ds-stat {
    background: #fff; border-radius: 1rem;
    padding: .9rem 1.1rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    transition: transform .15s, box-shadow .15s;
    border: 1.5px solid transparent;
}
.ds-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.11); }
.ds-stat-num  { font-size: 2rem; font-weight: 800; line-height: 1; }
.ds-stat-lbl  { font-size: .72rem; color: #6b7280; margin-top: .15rem; }
.ds-stat-icon {
    width: 40px; height: 40px; border-radius: .6rem;
    display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;
}
.ds-counter-anim { transition: color .3s; }

/* Gantt card accent */
body.role-designer .card.shadow-sm { border-radius: var(--ds-card-r) !important; }
body.role-designer .gantt-table thead th { background: var(--ds-accent); }
body.role-designer .day-header-cell.today { background: #4f46e5; }
</style>
@endpush

@section('content')

@php
    $today_str    = $today->toDateString();
    $totalDays    = count($dates);
    $colPct       = 100 / $totalDays;

    $totalOrders   = count($rows);
    $overdueCount  = collect($rows)->where('isOverdue', true)->count();
    $dueTodayCount = collect($rows)->where('daysLeft', 0)->count();
    $dueWeekCount  = collect($rows)->filter(fn($r) => $r['daysLeft'] >= 1 && $r['daysLeft'] <= 5)->count();
@endphp

{{-- ── Pill tabs ──────────────────────────────────────────── --}}
<ul class="nav ds-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.designer') }}">
            <i class="bi bi-grid me-1"></i>My Queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.designer.performance') }}">
            <i class="bi bi-bar-chart-line me-1"></i>Performance
        </a>
    </li>
    @if(auth()->user()->isDesigner())
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('schedule.gantt') }}">
            <i class="bi bi-bar-chart-steps me-1"></i>Deadline Map
        </a>
    </li>
    @endif
    <li class="nav-item ms-auto d-flex align-items-center">
        <span id="ds-clock"></span>
    </li>
</ul>

{{-- ── Stat pills ─────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="ds-stat d-flex align-items-center gap-3">
            <div class="ds-stat-icon bg-opacity-10" style="background:#e0e7ff;color:#4338ca">
                <i class="bi bi-pencil-square"></i>
            </div>
            <div>
                <div class="ds-stat-num ds-counter-anim" style="color:var(--ds-accent)" id="gs-total">{{ $totalOrders }}</div>
                <div class="ds-stat-lbl">Pending Designs</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ds-stat d-flex align-items-center gap-3 {{ $overdueCount > 0 ? '' : '' }}" style="{{ $overdueCount > 0 ? 'border-color:#ef4444' : '' }}">
            <div class="ds-stat-icon" style="background:#fee2e2;color:#dc2626">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <div class="ds-stat-num" style="color:#dc2626">{{ $overdueCount }}</div>
                <div class="ds-stat-lbl">Overdue</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ds-stat d-flex align-items-center gap-3" style="{{ $dueTodayCount > 0 ? 'border-color:#ef4444' : '' }}">
            <div class="ds-stat-icon" style="background:#fee2e2;color:#dc2626">
                <i class="bi bi-calendar-x"></i>
            </div>
            <div>
                <div class="ds-stat-num" style="color:#dc2626">{{ $dueTodayCount }}</div>
                <div class="ds-stat-lbl">Due Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ds-stat d-flex align-items-center gap-3">
            <div class="ds-stat-icon" style="background:#fef3c7;color:#b45309">
                <i class="bi bi-calendar-week"></i>
            </div>
            <div>
                <div class="ds-stat-num" style="color:#b45309">{{ $dueWeekCount }}</div>
                <div class="ds-stat-lbl">Due Within 5 Days</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Chart header ─────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <span class="fw-bold" style="color:var(--ds-accent);font-size:.75rem;text-transform:uppercase;letter-spacing:.7px">
            <i class="bi bi-map me-1"></i>Deadline Map
        </span>
        <span class="text-muted fw-normal ms-2" style="font-size:.8rem">
            {{ \Carbon\Carbon::parse($dates[0])->format('d M') }}
            → {{ \Carbon\Carbon::parse($dates[$totalDays-1])->format('d M Y') }}
        </span>
    </div>
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
                                    $dt  = \Carbon\Carbon::parse($d);
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

@push('scripts')
<script>
(function clock() {
    const el = document.getElementById('ds-clock');
    if (!el) return;
    function tick() {
        const now  = new Date();
        const pad  = n => String(n).padStart(2,'0');
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        el.textContent = days[now.getDay()] + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }
    tick(); setInterval(tick, 1000);
})();
</script>
@endpush
