@extends('layouts.app')

@section('title', 'Pipeline Manager Dashboard')
@section('page-title')
    <i class="bi bi-speedometer2 me-2 text-primary"></i>Pipeline Dashboard
@endsection

@section('content')

{{-- ── Tab navigation ─────────────────────────────────────── --}}
@php
    $activeTab = request('pieces_date') ? 'pieces' : 'overview';
@endphp
<ul class="nav nav-tabs mb-4" id="pipelineTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}"
                id="tab-overview-btn"
                data-bs-toggle="tab"
                data-bs-target="#tab-overview"
                type="button" role="tab"
                aria-controls="tab-overview"
                aria-selected="{{ $activeTab === 'overview' ? 'true' : 'false' }}">
            <i class="bi bi-speedometer2 me-1"></i>Overview
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'pieces' ? 'active' : '' }}"
                id="tab-pieces-btn"
                data-bs-toggle="tab"
                data-bs-target="#tab-pieces"
                type="button" role="tab"
                aria-controls="tab-pieces"
                aria-selected="{{ $activeTab === 'pieces' ? 'true' : 'false' }}">
            <i class="bi bi-stack me-1"></i>Pieces by Date
            @if($totalPiecesAll > 0 && $activeTab === 'pieces')
                <span class="badge bg-primary ms-1">{{ number_format($totalPiecesAll) }}</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content" id="pipelineTabContent">

{{-- ══════════════════════════════════════════════════════════
     TAB 1 — OVERVIEW (existing content, unchanged)
══════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}"
     id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-btn">

    {{-- ── Stat cards ─────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">

        <div class="col-6 col-sm-4 col-xl-2">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-card-list"></i></div>
                    <div>
                        <div class="stat-number text-primary">{{ $stats['total'] }}</div>
                        <div class="text-muted" style="font-size:.75rem">Total Orders</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-xl-2">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-pencil-square"></i></div>
                    <div>
                        <div class="stat-number text-info">{{ $stats['design'] }}</div>
                        <div class="text-muted" style="font-size:.75rem">In Design</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-xl-2">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-printer"></i></div>
                    <div>
                        <div class="stat-number text-warning">{{ $stats['print'] }}</div>
                        <div class="text-muted" style="font-size:.75rem">In Print</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-xl-2">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon" style="background:#f3e8ff;width:48px;height:48px;border-radius:.5rem;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                        <i class="bi bi-scissors" style="color:#7c3aed"></i>
                    </div>
                    <div>
                        <div class="stat-number" style="color:#7c3aed">{{ $stats['sew'] }}</div>
                        <div class="text-muted" style="font-size:.75rem">In Sewing</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-xl-2">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <div class="stat-number text-success">{{ $stats['ready'] }}</div>
                        <div class="text-muted" style="font-size:.75rem">Ready</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-xl-2">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-truck"></i></div>
                    <div>
                        <div class="stat-number text-secondary">{{ $stats['delivered'] }}</div>
                        <div class="text-muted" style="font-size:.75rem">Delivered</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Overtime warnings ──────────────────────────────────── --}}
    @include('partials.overtime-warnings', ['plan' => $plan])

    {{-- ── Late orders ────────────────────────────────────────── --}}
    @if(count($plan->lateOrders()) > 0)
        <div class="mb-4">
            <div class="section-title text-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Late Orders — {{ count($plan->lateOrders()) }} past delivery date
            </div>
            <div class="card border-danger shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle queue-table">
                        <thead class="table-danger">
                            <tr>
                                <th class="ps-3">Order</th>
                                <th>Customer</th>
                                <th class="text-center">Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Stage</th>
                                <th class="text-center">Priority</th>
                                <th class="text-center">Was Due</th>
                                <th class="text-center pe-3">Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plan->lateOrders() as $order)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $order->whatsappOrderId ?? $order->orderNumber }}</div>
                                        @if($order->whatsappOrderId)
                                            <div class="text-muted" style="font-size:.7rem">{{ $order->orderNumber }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $order->customerName }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                            {{ $order->productTypeLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ number_format($order->quantity) }}</td>
                                    <td class="text-center"><span class="badge bg-secondary">{{ ucfirst($order->department) }}</span></td>
                                    <td class="text-center"><span class="badge bg-{{ $order->priorityBadge() }}">{{ ucfirst($order->priority) }}</span></td>
                                    <td class="text-center text-danger fw-semibold">{{ \Carbon\Carbon::parse($order->deliveryDate)->format('d M Y') }}</td>
                                    <td class="text-center pe-3"><span class="badge bg-danger">{{ abs($order->daysUntilDelivery) }} day(s)</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Today's capacity utilisation ──────────────────────── --}}
    <div class="mb-4">
        <div class="section-title">
            <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Today's Capacity Utilisation
            <small class="text-muted fw-normal ms-2" style="font-size:.78rem">
                Higher is better — overtime means extra throughput, not just overload
            </small>
        </div>
        <div class="row g-3">

            {{-- Design (uncapped) --}}
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-pencil-square text-info fs-4"></i>
                            <span class="fw-semibold">Design</span>
                            <span class="badge bg-info bg-opacity-10 text-info ms-auto border border-info">Uncapped</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1" style="font-size:.82rem">
                            <span class="text-muted">Orders Today</span>
                            <strong>{{ count($plan->designQueue->orders) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3" style="font-size:.82rem">
                            <span class="text-muted">Total Units</span>
                            <strong>{{ number_format($plan->designQueue->totalUnits()) }}</strong>
                        </div>
                        @php $ds = $plan->designQueue->healthSummary(); @endphp
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-success">{{ $ds['green'] }} On Track</span>
                            <span class="badge bg-warning text-dark">{{ $ds['yellow'] }} At Risk</span>
                            <span class="badge bg-danger">{{ $ds['red'] }} Critical</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Printing --}}
            <div class="col-12 col-md-4">
                @include('partials._capacity-card', [
                    'queue'      => $plan->printQueue,
                    'icon'       => 'bi-printer',
                    'iconColor'  => 'text-warning',
                    'label'      => 'Printing',
                ])
            </div>

            {{-- Sewing --}}
            <div class="col-12 col-md-4">
                @include('partials._capacity-card', [
                    'queue'      => $plan->sewQueue,
                    'icon'       => 'bi-scissors',
                    'iconColor'  => '',
                    'iconStyle'  => 'color:#7c3aed',
                    'label'      => 'Sewing',
                ])
            </div>

        </div>
    </div>

    {{-- ── Today's queues ─────────────────────────────────────── --}}
    <div class="mb-4">
        <div class="section-title d-flex align-items-center justify-content-between">
            <span>
                <i class="bi bi-calendar-day me-2 text-primary"></i>
                Today's Production Queues
                <span class="text-muted fw-normal" style="font-size:.8rem">— {{ now()->format('l, d F Y') }}</span>
            </span>
            <a href="{{ route('history.index', ['department' => 'all']) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i>Full History
            </a>
        </div>
        <div class="row g-4">
            <div class="col-12 col-xl-4">
                @include('partials.queue-table', ['queue' => $plan->designQueue, 'title' => 'Design Queue',  'icon' => 'bi-pencil-square'])
            </div>
            <div class="col-12 col-xl-4">
                @include('partials.queue-table', ['queue' => $plan->printQueue,  'title' => 'Print Queue',   'icon' => 'bi-printer'])
            </div>
            <div class="col-12 col-xl-4">
                @include('partials.queue-table', ['queue' => $plan->sewQueue,    'title' => 'Sewing Queue',  'icon' => 'bi-scissors'])
            </div>
        </div>
    </div>

    {{-- ── Bottom row ─────────────────────────────────────────── --}}
    <div class="row g-4">

        <div class="col-12 col-lg-7">
            <div class="section-title">
                <i class="bi bi-exclamation-octagon-fill me-2 text-danger"></i>Critical Orders
            </div>
            <div class="card shadow-sm border-0">
                @if(count($plan->criticalOrders()) === 0)
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-shield-check fs-2 d-block mb-2 text-success"></i>
                        No critical orders right now
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 queue-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Order</th>
                                    <th>Customer</th>
                                    <th class="text-center">Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Stage</th>
                                    <th class="text-center">Priority</th>
                                    <th class="text-center pe-3">Delivery</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($plan->criticalOrders() as $order)
                                    <tr class="table-danger">
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="health-dot bg-danger"></span>
                                                <div>
                                                    <a href="{{ route('orders.show', $order->orderId) }}" class="fw-semibold text-decoration-none text-dark">
                                                        {{ $order->whatsappOrderId ?? $order->orderNumber }}
                                                    </a>
                                                    @if($order->whatsappOrderId)
                                                        <div class="text-muted" style="font-size:.7rem">{{ $order->orderNumber }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $order->customerName }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border" style="font-size:.72rem">{{ $order->productTypeLabel }}</span>
                                        </td>
                                        <td class="text-center fw-semibold">{{ number_format($order->quantity) }}</td>
                                        <td class="text-center"><span class="badge bg-dark">{{ ucfirst($order->department) }}</span></td>
                                        <td class="text-center"><span class="badge bg-{{ $order->priorityBadge() }}">{{ ucfirst($order->priority) }}</span></td>
                                        <td class="text-center pe-3">
                                            <span class="text-danger fw-semibold" style="font-size:.85rem">
                                                {{ \Carbon\Carbon::parse($order->deliveryDate)->format('d M Y') }}
                                            </span>
                                            @if($order->isLate)
                                                <br><span class="badge bg-danger">{{ abs($order->daysUntilDelivery) }}d LATE</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="section-title">
                <i class="bi bi-truck me-2 text-primary"></i>Upcoming Deliveries
            </div>
            @include('partials.upcoming-deliveries', ['upcomingOrders' => $upcomingOrders])
        </div>

    </div>

</div>{{-- /tab-overview --}}


{{-- ══════════════════════════════════════════════════════════
     TAB 2 — PIECES BY DATE
══════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade {{ $activeTab === 'pieces' ? 'show active' : '' }}"
     id="tab-pieces" role="tabpanel" aria-labelledby="tab-pieces-btn">

    {{-- ── Date picker form ──────────────────────────────────── --}}
    <form method="GET" action="{{ route('dashboard.pipeline') }}" class="mb-4">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label for="pieces_date" class="form-label fw-semibold mb-1" style="font-size:.85rem">
                    Select Date
                </label>
                <input type="date"
                       id="pieces_date"
                       name="pieces_date"
                       class="form-control"
                       value="{{ $selectedDate }}"
                       style="width:200px">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Load
            </button>
            @if($selectedDate !== now()->toDateString())
                <a href="{{ route('dashboard.pipeline', ['pieces_date' => now()->toDateString()]) }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-calendar-check me-1"></i>Today
                </a>
            @endif
        </div>
    </form>

    @php
        $selectedCarbon = \Carbon\Carbon::parse($selectedDate);
        $isToday        = $selectedDate === now()->toDateString();
        $isFuture       = $selectedCarbon->isAfter(now()->startOfDay());
    @endphp

    {{-- ── Summary cards ─────────────────────────────────────── --}}
    <div class="row g-3 mb-4">

        {{-- Grand total --}}
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"
                         style="width:56px;height:56px;border-radius:.6rem;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0">
                        <i class="bi bi-stack"></i>
                    </div>
                    <div>
                        <div style="font-size:1.9rem;font-weight:700;line-height:1.1" class="text-primary">
                            {{ number_format($totalPiecesAll) }}
                        </div>
                        <div class="text-muted" style="font-size:.78rem">
                            Total Pieces for {{ $selectedCarbon->format('d M Y') }}
                        </div>
                        @if($totalPiecesLate > 0)
                            <div class="mt-1">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger" style="font-size:.7rem">
                                    includes {{ number_format($totalPiecesLate) }} late
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Due on date --}}
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"
                         style="width:56px;height:56px;border-radius:.6rem;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div style="font-size:1.9rem;font-weight:700;line-height:1.1" class="text-success">
                            {{ number_format($totalPiecesDue) }}
                        </div>
                        <div class="text-muted" style="font-size:.78rem">
                            Pieces Due on {{ $selectedCarbon->format('d M Y') }}
                        </div>
                        <div class="text-muted mt-1" style="font-size:.72rem">
                            {{ $dueOnDate->sum('order_count') }} order(s)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Late carry-over --}}
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card shadow-sm h-100 {{ $totalPiecesLate > 0 ? 'border-danger' : 'border-0' }}">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <div style="width:56px;height:56px;border-radius:.6rem;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;background:{{ $totalPiecesLate > 0 ? '#fee2e2' : '#f0fdf4' }};color:{{ $totalPiecesLate > 0 ? '#dc2626' : '#16a34a' }}">
                        <i class="bi bi-{{ $totalPiecesLate > 0 ? 'exclamation-triangle-fill' : 'check-circle-fill' }}"></i>
                    </div>
                    <div>
                        <div style="font-size:1.9rem;font-weight:700;line-height:1.1;color:{{ $totalPiecesLate > 0 ? '#dc2626' : '#16a34a' }}">
                            {{ number_format($totalPiecesLate) }}
                        </div>
                        <div class="text-muted" style="font-size:.78rem">Late Carry-Over</div>
                        @if($totalPiecesLate > 0)
                            <div class="text-muted mt-1" style="font-size:.72rem">
                                {{ $lateCarryOver->sum('order_count') }} order(s) past due
                            </div>
                        @else
                            <div class="text-success mt-1" style="font-size:.72rem">No late orders</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Breakdown by order type ─────────────────────────────── --}}
    <div class="row g-4 mb-4">

        {{-- Due on date — by type --}}
        <div class="col-12 col-lg-6">
            <div class="section-title">
                <i class="bi bi-calendar-event me-2 text-success"></i>
                Due on {{ $selectedCarbon->format('d M Y') }} — by Order Type
            </div>
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Order Type</th>
                                <th class="text-center">Orders</th>
                                <th class="text-center">Pieces</th>
                                <th class="text-end pe-3">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandDue = max(1, $totalPiecesDue); @endphp
                            @forelse($productTypes as $typeKey => $typeLabel)
                                @php
                                    $row        = $dueOnDate->get($typeKey);
                                    $pieces     = $row ? (int) $row->total_pieces : 0;
                                    $orders     = $row ? (int) $row->order_count  : 0;
                                    $sharePct   = $totalPiecesDue > 0 ? round($pieces / $grandDue * 100) : 0;
                                @endphp
                                @if($pieces > 0)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-secondary border me-1" style="font-size:.75rem">{{ $typeLabel }}</span>
                                        </td>
                                        <td class="text-center text-muted" style="font-size:.85rem">{{ $orders }}</td>
                                        <td class="text-center fw-semibold">{{ number_format($pieces) }}</td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div style="width:60px;height:6px;background:#e9ecef;border-radius:3px;overflow:hidden">
                                                    <div style="width:{{ $sharePct }}%;height:100%;background:#0d6efd;border-radius:3px"></div>
                                                </div>
                                                <span style="font-size:.8rem;min-width:32px;text-align:right">{{ $sharePct }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                            @endforelse
                            @if($totalPiecesDue === 0)
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4" style="font-size:.85rem">
                                        <i class="bi bi-inbox d-block fs-4 mb-1"></i>
                                        No orders due on this date
                                    </td>
                                </tr>
                            @else
                                <tr class="table-light fw-semibold">
                                    <td class="ps-3">Total</td>
                                    <td class="text-center">{{ $dueOnDate->sum('order_count') }}</td>
                                    <td class="text-center">{{ number_format($totalPiecesDue) }}</td>
                                    <td class="text-end pe-3">100%</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Late carry-over — by type --}}
        <div class="col-12 col-lg-6">
            <div class="section-title {{ $totalPiecesLate > 0 ? 'text-danger' : '' }}">
                <i class="bi bi-exclamation-triangle{{ $totalPiecesLate > 0 ? '-fill' : '' }} me-2"></i>
                Late Carry-Over — by Order Type
                @if(!$isFuture && $totalPiecesLate > 0)
                    <small class="fw-normal text-muted ms-1" style="font-size:.75rem">added to today's workload</small>
                @endif
            </div>
            <div class="card shadow-sm {{ $totalPiecesLate > 0 ? 'border-danger' : 'border-0' }}">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="{{ $totalPiecesLate > 0 ? 'table-danger' : 'table-light' }}">
                            <tr>
                                <th class="ps-3">Order Type</th>
                                <th class="text-center">Orders</th>
                                <th class="text-center">Pieces</th>
                                <th class="text-end pe-3">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandLate = max(1, $totalPiecesLate); @endphp
                            @forelse($productTypes as $typeKey => $typeLabel)
                                @php
                                    $row      = $lateCarryOver->get($typeKey);
                                    $pieces   = $row ? (int) $row->total_pieces : 0;
                                    $orders   = $row ? (int) $row->order_count  : 0;
                                    $sharePct = $totalPiecesLate > 0 ? round($pieces / $grandLate * 100) : 0;
                                @endphp
                                @if($pieces > 0)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-secondary border me-1" style="font-size:.75rem">{{ $typeLabel }}</span>
                                        </td>
                                        <td class="text-center text-muted" style="font-size:.85rem">{{ $orders }}</td>
                                        <td class="text-center fw-semibold text-danger">{{ number_format($pieces) }}</td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div style="width:60px;height:6px;background:#fecaca;border-radius:3px;overflow:hidden">
                                                    <div style="width:{{ $sharePct }}%;height:100%;background:#dc2626;border-radius:3px"></div>
                                                </div>
                                                <span style="font-size:.8rem;min-width:32px;text-align:right">{{ $sharePct }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                            @endforelse
                            @if($totalPiecesLate === 0)
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4" style="font-size:.85rem">
                                        <i class="bi bi-check-circle-fill d-block fs-4 mb-1 text-success"></i>
                                        No late carry-over
                                    </td>
                                </tr>
                            @else
                                <tr class="table-danger fw-semibold">
                                    <td class="ps-3">Total</td>
                                    <td class="text-center">{{ $lateCarryOver->sum('order_count') }}</td>
                                    <td class="text-center text-danger">{{ number_format($totalPiecesLate) }}</td>
                                    <td class="text-end pe-3">100%</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Orders due on selected date — detail table ────────── --}}
    <div class="mb-4">
        <div class="section-title">
            <i class="bi bi-calendar-check me-2 text-success"></i>
            Orders Due on {{ $selectedCarbon->format('d M Y') }}
            <span class="text-muted fw-normal ms-1" style="font-size:.75rem">— {{ $ordersOnDate->count() }} order(s)</span>
        </div>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 queue-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Order</th>
                            <th>Customer</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Stage</th>
                            <th class="text-center pe-3">Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordersOnDate as $order)
                            @php
                                $priorityBadge = match($order->priority) {
                                    'critical' => 'danger',
                                    'rush'     => 'warning text-dark',
                                    default    => 'secondary',
                                };
                            @endphp
                            <tr class="{{ $order->priority === 'critical' ? 'table-danger' : ($order->priority === 'rush' ? 'table-warning' : '') }}">
                                <td class="ps-3">
                                    <a href="{{ route('orders.show', $order->id) }}"
                                       class="fw-semibold text-decoration-none text-dark">
                                        {{ $order->whatsapp_order_id ?? $order->order_number }}
                                    </a>
                                    @if($order->whatsapp_order_id)
                                        <div class="text-muted" style="font-size:.7rem">{{ $order->order_number }}</div>
                                    @endif
                                </td>
                                <td>{{ $order->customer_name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                        {{ $order->productTypeLabel }}
                                    </span>
                                </td>
                                <td class="text-center fw-semibold">{{ number_format($order->quantity) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ ucfirst($order->stage) }}</span>
                                </td>
                                <td class="text-center pe-3">
                                    <span class="badge bg-{{ $priorityBadge }}">{{ ucfirst($order->priority) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4" style="font-size:.85rem">
                                    <i class="bi bi-inbox d-block fs-4 mb-1"></i>
                                    No orders due on this date
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($ordersOnDate->isNotEmpty())
                        <tfoot class="table-light">
                            <tr class="fw-semibold">
                                <td class="ps-3" colspan="3">Total</td>
                                <td class="text-center">{{ number_format($ordersOnDate->sum('quantity')) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- ── Late orders detail table ────────────────────────────── --}}
    @if($lateOrders->isNotEmpty())
        <div class="mb-4">
            <div class="section-title text-danger">
                <i class="bi bi-clock-history me-2"></i>
                Late Orders Detail — {{ $lateOrders->count() }} order(s) past due
                <small class="fw-normal text-muted ms-2" style="font-size:.75rem">
                    delivery date before {{ $selectedCarbon->format('d M Y') }}, not yet delivered
                </small>
            </div>
            <div class="card border-danger shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 queue-table">
                        <thead class="table-danger">
                            <tr>
                                <th class="ps-3">Order</th>
                                <th>Customer</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Stage</th>
                                <th class="text-center">Priority</th>
                                <th class="text-center">Was Due</th>
                                <th class="text-center pe-3">Days Late</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lateOrders as $order)
                                @php
                                    $daysLate = (int) \Carbon\Carbon::parse($order->delivery_date)
                                        ->startOfDay()
                                        ->diffInDays(now()->startOfDay());
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('orders.show', $order->id) }}"
                                           class="fw-semibold text-decoration-none text-dark">
                                            {{ $order->whatsapp_order_id ?? $order->order_number }}
                                        </a>
                                        @if($order->whatsapp_order_id)
                                            <div class="text-muted" style="font-size:.7rem">{{ $order->order_number }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                            {{ $order->productTypeLabel }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-semibold">{{ number_format($order->quantity) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ ucfirst($order->stage) }}</span>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $priorityBadge = match($order->priority) {
                                                'critical' => 'danger',
                                                'rush'     => 'warning text-dark',
                                                default    => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $priorityBadge }}">{{ ucfirst($order->priority) }}</span>
                                    </td>
                                    <td class="text-center text-danger fw-semibold">
                                        {{ $order->delivery_date->format('d M Y') }}
                                    </td>
                                    <td class="text-center pe-3">
                                        <span class="badge bg-danger">{{ $daysLate }} day(s)</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>{{-- /tab-pieces --}}

</div>{{-- /tab-content --}}

@endsection
