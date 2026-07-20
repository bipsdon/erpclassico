@extends('layouts.app')

@section('title', 'Designer Dashboard')
@section('page-title')
    <i class="bi bi-pencil-square me-2 text-info"></i>Design Dashboard
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- STAT CARDS                                                 --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <div class="stat-number text-info">{{ count($queue->orders) }}</div>
                    <div class="text-muted" style="font-size:.75rem">Today's Queue</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
                <div>
                    <div class="stat-number text-danger">{{ $queue->healthSummary()['red'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">Critical</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-number text-warning">{{ $queue->healthSummary()['yellow'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">At Risk</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number text-success">{{ $queue->healthSummary()['green'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">On Track</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ── MAIN LAYOUT: Queue (left) + Side widgets (right)           --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('history.index', ['department' => 'design']) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-clock-history me-1"></i>Design History
    </a>
</div>
<div class="row g-4">

    {{-- Today's Design Queue --}}
    <div class="col-12 col-xl-8">
        <div class="section-title">
            <i class="bi bi-calendar-day me-2 text-info"></i>
            Today's Design Queue
            <span class="text-muted fw-normal" style="font-size:.8rem">
                — {{ now()->format('l, d F Y') }}
            </span>
        </div>
        @include('partials.queue-table', [
            'queue'            => $queue,
            'title'            => 'Design Queue',
            'icon'             => 'bi-pencil-square',
            'showCompletedBtn' => true,
        ])
    </div>

    {{-- Right column: Upcoming + Critical --}}
    <div class="col-12 col-xl-4">

        {{-- Critical orders this designer needs to prioritise --}}
        <div class="section-title">
            <i class="bi bi-exclamation-octagon-fill me-2 text-danger"></i>Needs Immediate Attention
        </div>
        <div class="card shadow-sm border-0 mb-4">
            @php
                $criticalDesign = array_filter(
                    $queue->orders,
                    fn($o) => $o->healthStatus === 'red'
                );
            @endphp
            @if(empty($criticalDesign))
                <div class="card-body text-center py-4 text-muted">
                    <i class="bi bi-shield-check fs-2 d-block mb-2 text-success"></i>
                    All design orders are on track
                </div>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($criticalDesign as $order)
                        <li class="list-group-item list-group-item-danger d-flex justify-content-between align-items-center px-3 py-2">
                            <div>
                                <div class="fw-semibold" style="font-size:.875rem">
                                    {{ $order->whatsappOrderId ?? $order->orderNumber }}
                                </div>
                                @if($order->whatsappOrderId)
                                    <div class="text-muted" style="font-size:.72rem">{{ $order->orderNumber }}</div>
                                @endif
                                <div class="text-muted" style="font-size:.75rem">
                                    {{ $order->customerName }} · {{ $order->productTypeLabel }} · {{ number_format($order->quantity) }} units
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $order->priorityBadge() }} d-block mb-1">
                                    {{ ucfirst($order->priority) }}
                                </span>
                                @if($order->isLate)
                                    <span class="badge bg-danger">{{ abs($order->daysUntilDelivery) }}d LATE</span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        {{ $order->daysUntilDelivery }}d left
                                    </span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Upcoming deliveries --}}
        <div class="section-title">
            <i class="bi bi-truck me-2 text-primary"></i>Upcoming Deliveries
        </div>
        @include('partials.upcoming-deliveries', ['upcomingOrders' => $upcomingOrders])

    </div>

</div>

{{-- ── Performance charts ──────────────────────────────────── --}}
@include('partials.perf-charts', [
    'perf'        => $perf,
    'accentColor' => '#0dcaf0',
    'accentRgb'   => '13,202,240',
])

@endsection
