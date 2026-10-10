@extends('layouts.app')

@section('title', 'Designer Dashboard')
@section('page-title')
    <i class="bi bi-pencil-square me-2 text-info"></i>Design Dashboard
@endsection

@section('content')

{{-- ── Tab nav ─────────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard.designer') }}">
            <i class="bi bi-pencil-square me-1"></i>Queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.designer.performance') }}">
            <i class="bi bi-bar-chart-line me-1"></i>Performance
        </a>
    </li>
</ul>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- STAT CARDS                                                 --}}
{{-- ══════════════════════════════════════════════════════════ --}}
@php
    $noBriefOrders  = array_filter($queue->orders, fn($o) => $o->isMissingBrief());
    $noBriefCount   = count($noBriefOrders);
    $health         = $queue->healthSummary();
@endphp

<div class="row g-3 mb-4">

    <div class="col-6 col-sm-3 col-lg">
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

    <div class="col-6 col-sm-3 col-lg">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
                <div>
                    <div class="stat-number text-danger">{{ $health['red'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">Critical</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3 col-lg">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-number text-warning">{{ $health['yellow'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">At Risk</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-3 col-lg">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number text-success">{{ $health['green'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">On Track</div>
                </div>
            </div>
        </div>
    </div>

    {{-- No Brief stat card — only shown when there are problem orders --}}
    <div class="col-6 col-sm-3 col-lg">
        <div class="card stat-card shadow-sm h-100 {{ $noBriefCount > 0 ? 'border-warning border-2' : '' }}">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="stat-icon {{ $noBriefCount > 0 ? 'bg-warning bg-opacity-25 text-warning' : 'bg-light text-muted' }}">
                    <i class="bi bi-file-earmark-x"></i>
                </div>
                <div>
                    <div class="stat-number {{ $noBriefCount > 0 ? 'text-warning' : 'text-muted' }}">{{ $noBriefCount }}</div>
                    <div class="text-muted" style="font-size:.75rem">No Brief</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ── No-brief warning panel ──────────────────────────────── --}}
@if($noBriefCount > 0)
    <div class="card border-warning shadow-sm mb-4" style="border-left:4px solid #ffc107 !important">
        <div class="card-header bg-warning bg-opacity-10 py-2 d-flex align-items-center justify-content-between">
            <span class="fw-semibold text-warning-emphasis" style="font-size:.875rem">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $noBriefCount }} order{{ $noBriefCount !== 1 ? 's' : '' }} have no brief or spec — you cannot start these without more info
            </span>
            <span class="badge bg-warning text-dark">Ask Pipeline Manager</span>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                @foreach($noBriefOrders as $order)
                    <li class="list-group-item d-flex align-items-center justify-content-between px-3 py-2 gap-3">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <span class="health-dot bg-{{ $order->healthBadge() }}"></span>
                            <div>
                                <div class="fw-semibold" style="font-size:.875rem">
                                    @if($order->whatsappOrderId)
                                        <i class="bi bi-whatsapp text-success me-1"></i>{{ $order->whatsappOrderId }}
                                    @else
                                        {{ $order->orderNumber }}
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:.75rem">
                                    {{ $order->customerName }}
                                    <span class="mx-1">·</span>
                                    {{ number_format($order->quantity) }} {{ $order->productTypeLabel }}
                                </div>
                            </div>
                        </div>

                        {{-- Urgency chip --}}
                        <span class="days-chip flex-shrink-0
                            {{ $order->daysUntilDelivery < 0 ? 'bg-danger text-white' : ($order->daysUntilDelivery <= 1 ? 'bg-warning text-dark' : 'bg-light border text-secondary') }}"
                            style="font-size:.7rem;font-weight:600;white-space:nowrap">
                            {{ $order->urgencyLabel() }}
                        </span>

                        {{-- Priority badge --}}
                        <span class="badge bg-{{ $order->priorityBadge() }} flex-shrink-0">
                            {{ ucfirst($order->priority) }}
                        </span>

                        {{-- Open link --}}
                        <a href="{{ route('orders.show', $order->orderId) }}"
                           class="btn btn-sm btn-outline-warning flex-shrink-0"
                           style="font-size:.78rem">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- ── MAIN LAYOUT: Queue (left) + Side widgets (right)           --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('history.index', ['department' => 'design']) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-clock-history me-1"></i>Design History
    </a>
</div>

{{-- ── Do This Next banner ─────────────────────────────── --}}
@include('partials.do-this-next', ['queue' => $queue])

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

    {{-- Right column: Urgent + Upcoming --}}
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
                        <li class="list-group-item list-group-item-danger px-3 py-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-semibold" style="font-size:.875rem">
                                        <a href="{{ route('orders.show', $order->orderId) }}"
                                           class="text-decoration-none text-danger-emphasis">
                                            {{ $order->whatsappOrderId ?? $order->orderNumber }}
                                        </a>
                                    </div>
                                    @if($order->whatsappOrderId)
                                        <div class="text-muted" style="font-size:.72rem">{{ $order->orderNumber }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size:.75rem">
                                        {{ $order->customerName }} · {{ $order->productTypeLabel }} · {{ number_format($order->quantity) }} units
                                    </div>
                                    {{-- Brief status inline --}}
                                    @if($order->isMissingBrief())
                                        <span class="badge bg-warning text-dark mt-1" style="font-size:.65rem">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>No brief
                                        </span>
                                    @elseif($order->attachmentsCount > 0)
                                        <span class="badge bg-light border text-secondary mt-1" style="font-size:.65rem">
                                            <i class="bi bi-paperclip me-1"></i>{{ $order->attachmentsCount }} file{{ $order->attachmentsCount !== 1 ? 's' : '' }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-end flex-shrink-0">
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

@endsection
