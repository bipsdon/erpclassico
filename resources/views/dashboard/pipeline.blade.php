@extends('layouts.app')

@section('title', 'Pipeline Manager Dashboard')
@section('page-title')
    <i class="bi bi-speedometer2 me-2 text-primary"></i>Pipeline Dashboard
@endsection

@section('content')

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

@endsection
