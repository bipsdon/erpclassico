@extends('layouts.app')

@section('title', 'Printing Manager Dashboard')
@section('page-title')
    <i class="bi bi-printer me-2 text-warning"></i>Printing Dashboard
@endsection

@section('content')

{{-- ── Overtime top-banner ─────────────────────────────────── --}}
@if($queue->hasOvertime())
    <div class="alert alert-danger overtime-alert d-flex align-items-center gap-3 mb-4 shadow-sm" role="alert">
        <i class="bi bi-alarm-fill fs-3 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <div class="fw-bold fs-5">PRINTING OVERTIME REQUIRED TODAY</div>
            <div class="d-flex flex-wrap gap-4 mt-1" style="font-size:.9rem">
                <span>Workload: <strong>{{ $queue->loadPercent() }}%</strong> of a day</span>
                <span class="fw-bold text-danger">
                    <i class="bi bi-plus-circle-fill me-1"></i>
                    +<strong>{{ $queue->overtimePercent() }}%</strong> beyond full capacity
                </span>
            </div>
            @if(!empty($queue->unitsByProductType))
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach($queue->unitsByProductType as $type => $units)
                        <span class="badge bg-white text-dark border">
                            {{ $units }} {{ \App\Models\CapacityConfig::productTypes()[$type] ?? $type }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
        <span class="badge bg-warning text-dark fs-5 flex-shrink-0">+{{ $queue->overtimePercent() }}%</span>
    </div>
@endif

{{-- ── Stat cards ─────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-printer"></i></div>
                <div>
                    <div class="stat-number text-warning">{{ count($queue->orders) }}</div>
                    <div class="text-muted" style="font-size:.75rem">Today's Jobs</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-shirt"></i></div>
                <div>
                    <div class="stat-number text-primary">{{ number_format($queue->totalUnits()) }}</div>
                    <div class="text-muted" style="font-size:.75rem">Units Required</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-{{ $queue->utilisationColour() }} bg-opacity-10 text-{{ $queue->utilisationColour() }}">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div>
                    <div class="stat-number text-{{ $queue->utilisationColour() }}">{{ $queue->loadPercent() }}%</div>
                    <div class="text-muted" style="font-size:.75rem">Day Load</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                @if($queue->hasOvertime())
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-plus-circle"></i></div>
                    <div>
                        <div class="stat-number text-warning">+{{ $queue->overtimePercent() }}%</div>
                        <div class="text-muted" style="font-size:.75rem">Overtime</div>
                    </div>
                @else
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-all"></i></div>
                    <div>
                        <div class="stat-number text-success">OK</div>
                        <div class="text-muted" style="font-size:.75rem">Within Capacity</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Capacity bar ────────────────────────────────────────── --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold"><i class="bi bi-printer me-2 text-warning"></i>Printing Workload Today</span>
            <span class="badge bg-{{ $queue->utilisationColour() }} fs-6 px-3">{{ $queue->loadPercent() }}% of a day</span>
        </div>
        <div class="progress mb-1" style="height:20px;border-radius:10px">
            <div class="progress-bar bg-{{ $queue->utilisationColour() }}"
                 style="width:{{ $queue->normalBarWidth() }}%">
                @if($queue->loadPercent() >= 25) {{ min(100, $queue->loadPercent()) }}% @endif
            </div>
            @if($queue->hasOvertime())
                <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                     style="width:{{ $queue->overtimeBarWidth() }}%">
                    +{{ $queue->overtimePercent() }}%
                </div>
            @endif
        </div>
        <div class="d-flex justify-content-between mb-3" style="font-size:.75rem;color:#adb5bd">
            <span>0%</span><span>100% = full working day</span>
        </div>
        {{-- Product type breakdown --}}
        @if(!empty($queue->unitsByProductType))
            <div class="d-flex flex-wrap gap-2">
                @foreach($queue->unitsByProductType as $type => $units)
                    @php $rate = \App\Models\CapacityConfig::rateFor('print', $type); @endphp
                    <div class="badge bg-light text-dark border px-3 py-2" style="font-size:.8rem">
                        <strong>{{ $units }}</strong>
                        {{ \App\Models\CapacityConfig::productTypes()[$type] ?? $type }}
                        <span class="text-muted ms-1">({{ $rate }}/day rate → {{ round(($units/$rate)*100) }}% of day)</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ── Queue + Side ────────────────────────────────────────── --}}
<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('history.index', ['department' => 'print']) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-clock-history me-1"></i>Print History
    </a>
</div>
<div class="row g-4">

    <div class="col-12 col-xl-8">
        <div class="section-title">
            <i class="bi bi-calendar-day me-2 text-warning"></i>
            Today's Print Queue
            <span class="text-muted fw-normal" style="font-size:.8rem">— {{ now()->format('l, d F Y') }}</span>
        </div>
        @include('partials.queue-table', [
            'queue'               => $queue,
            'title'               => 'Print Queue',
            'icon'                => 'bi-printer',
            'showCompletedBtn'    => true,
            'showWhatsappPrimary' => true,
        ])

        {{-- ── Coming Tomorrow ──────────────────────────────────── --}}
        @if(count($tomorrowQueue->orders) > 0)
            <div class="section-title mt-4">
                <i class="bi bi-calendar2-arrow me-2 text-secondary"></i>
                Coming Tomorrow
                <span class="text-muted fw-normal" style="font-size:.8rem">
                    — {{ now()->addDay()->format('l, d F Y') }}
                </span>
            </div>
            @include('partials.queue-table', [
                'queue'               => $tomorrowQueue,
                'title'               => 'Tomorrow\'s Print Queue',
                'icon'                => 'bi-printer',
                'showCompletedBtn'    => false,
                'showWhatsappPrimary' => true,
            ])
        @endif
    </div>

    <div class="col-12 col-xl-4">

        <div class="section-title"><i class="bi bi-fire me-2 text-danger"></i>Priority Jobs Today</div>
        <div class="card shadow-sm border-0 mb-4">
            @php
                $priorityJobs = array_filter($queue->orders, fn($o) => in_array($o->priority, ['critical', 'rush']));
            @endphp
            @if(empty($priorityJobs))
                <div class="card-body text-center py-4 text-muted">
                    <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                    No rush or critical print jobs today
                </div>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($priorityJobs as $order)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2
                            {{ $order->priority === 'critical' ? 'list-group-item-danger' : 'list-group-item-warning' }}">
                            <div>
                                <div class="fw-semibold" style="font-size:.875rem">
                                    @if($order->whatsappOrderId)
                                        <i class="bi bi-whatsapp text-success me-1"></i>{{ $order->whatsappOrderId }}
                                    @else
                                        {{ $order->orderNumber }}
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    {{ $order->customerName }} · {{ $order->productTypeLabel }} · {{ number_format($order->quantity) }} units
                                    <span class="ms-1 badge bg-light text-secondary border" style="font-size:.65rem">{{ $order->dayPercent() }}% of day</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $order->priorityBadge() }} d-block mb-1">{{ ucfirst($order->priority) }}</span>
                                <span class="days-chip {{ $order->isLate ? 'bg-danger text-white' : ($order->daysUntilDelivery <= 1 ? 'bg-warning text-dark' : 'bg-light border text-secondary') }}">
                                    {{ $order->isLate ? abs($order->daysUntilDelivery).'d LATE' : $order->daysUntilDelivery.'d left' }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="section-title"><i class="bi bi-truck me-2 text-primary"></i>Upcoming Deliveries</div>
        @include('partials.upcoming-deliveries', ['upcomingOrders' => $upcomingOrders])

    </div>

</div>

{{-- ── Performance charts ──────────────────────────────────── --}}
@include('partials.perf-charts', [
    'perf'        => $perf,
    'perfMine'    => $perfMine,
    'accentColor' => '#ffc107',
    'accentRgb'   => '255,193,7',
])

@endsection
