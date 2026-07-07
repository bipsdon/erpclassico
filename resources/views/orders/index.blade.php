@extends('layouts.app')

@section('title', 'Orders')
@section('page-title')
    <i class="bi bi-card-list me-2 text-primary"></i>All Orders
@endsection

@section('content')

{{-- ── Stage filter tabs ─────────────────────────────────── --}}
<div class="mb-3">
    <ul class="nav nav-pills gap-1 flex-wrap">
        @php
            $stages = [
                ''          => ['label' => 'All',     'icon' => 'bi-grid',          'color' => 'secondary'],
                'design'    => ['label' => 'Design',  'icon' => 'bi-pencil-square', 'color' => 'info'],
                'print'     => ['label' => 'Print',   'icon' => 'bi-printer',       'color' => 'warning'],
                'sew'       => ['label' => 'Sewing',  'icon' => 'bi-scissors',      'color' => 'purple'],
                'ready'     => ['label' => 'Ready',   'icon' => 'bi-box-seam',      'color' => 'success'],
                'delivered' => ['label' => 'Delivered','icon' => 'bi-truck',         'color' => 'dark'],
            ];
            $currentStage = request('stage', '');
        @endphp

        @foreach($stages as $value => $meta)
            <li class="nav-item">
                <a href="{{ request()->fullUrlWithQuery(['stage' => $value, 'page' => 1]) }}"
                   class="nav-link py-1 px-3 {{ $currentStage === $value ? 'active' : 'bg-white border' }}"
                   style="{{ $currentStage !== $value ? 'color:#495057' : '' }}">
                    <i class="bi {{ $meta['icon'] }} me-1"></i>
                    {{ $meta['label'] }}
                    @if($value !== '' && isset($stageCounts[$value]))
                        <span class="badge bg-white text-dark ms-1">{{ $stageCounts[$value] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</div>

{{-- ── Toolbar: search + filters + create ───────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('orders.index') }}"
              class="row g-2 align-items-center">
            @if(request('stage'))
                <input type="hidden" name="stage" value="{{ request('stage') }}">
            @endif

            {{-- Search --}}
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Order #, customer name, phone…"
                           value="{{ request('search') }}">
                </div>
            </div>

            {{-- Priority filter --}}
            <div class="col-6 col-md-2">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <option value="normal"   {{ request('priority') === 'normal'   ? 'selected' : '' }}>Normal</option>
                    <option value="rush"     {{ request('priority') === 'rush'     ? 'selected' : '' }}>Rush</option>
                    <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                </select>
            </div>

            {{-- Status filter --}}
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending"     {{ request('status') === 'pending'     ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed"   {{ request('status') === 'completed'   ? 'selected' : '' }}>Completed</option>
                    <option value="on_hold"     {{ request('status') === 'on_hold'     ? 'selected' : '' }}>On Hold</option>
                    <option value="cancelled"   {{ request('status') === 'cancelled'   ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>

            @auth
                @if(auth()->user()->isPipelineManager())
                    <div class="col-auto ms-auto">
                        <a href="{{ route('orders.create') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-lg me-1"></i>New Order
                        </a>
                    </div>
                @endif
            @endauth
        </form>
    </div>
</div>

{{-- ── Orders table ──────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 queue-table">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:1%"></th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th class="text-center">Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Stage</th>
                    <th class="text-center">Priority</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Delivery</th>
                    <th class="text-center">Players</th>
                    <th class="pe-3 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr class="{{ $order->is_late ? 'table-danger' : '' }}">
                        {{-- Late indicator --}}
                        <td class="ps-3">
                            @if($order->is_late)
                                <i class="bi bi-exclamation-triangle-fill text-danger"
                                   title="LATE — was due {{ $order->delivery_date->format('d M Y') }}"></i>
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('orders.show', $order) }}"
                               class="fw-semibold text-decoration-none text-dark">
                                {{ $order->order_number }}
                            </a>
                        </td>

                        <td>
                            <div style="font-size:.875rem">{{ $order->customer_name }}</div>
                            <div class="text-muted" style="font-size:.75rem">{{ $order->customer_phone }}</div>
                        </td>

                        <td class="text-center fw-semibold">{{ number_format($order->quantity) }}</td>

                        <td class="text-center">
                            <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                {{ $order->product_type_label }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $order->stage_label }}</span>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-{{ $order->priority_badge }}">
                                {{ ucfirst($order->priority) }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-{{ $order->status_badge }}">
                                {{ ucwords(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>

                        <td class="text-center">
                            <div style="font-size:.8rem">
                                {{ $order->delivery_date->format('d M Y') }}
                            </div>
                            <span class="days-chip
                                {{ $order->days_remaining < 0 ? 'bg-danger text-white' : ($order->days_remaining <= 1 ? 'bg-warning text-dark' : 'bg-light border text-secondary') }}">
                                @if($order->days_remaining < 0)
                                    {{ abs($order->days_remaining) }}d late
                                @elseif($order->days_remaining === 0)
                                    Today
                                @else
                                    {{ $order->days_remaining }}d
                                @endif
                            </span>
                        </td>

                        <td class="text-center">
                            @if($order->players_count > 0)
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-people me-1"></i>{{ $order->players_count }}
                                </span>
                            @else
                                <span class="text-muted" style="font-size:.8rem">—</span>
                            @endif
                        </td>

                        <td class="pe-3 text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('orders.show', $order) }}"
                                   class="btn btn-outline-secondary"
                                   title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @auth
                                    @if(auth()->user()->isPipelineManager())
                                        <a href="{{ route('orders.edit', $order) }}"
                                           class="btn btn-outline-primary"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST"
                                              action="{{ route('orders.destroy', $order) }}"
                                              onsubmit="return confirm('Delete order {{ $order->order_number }}? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No orders found. Try clearing your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }}
                of {{ $orders->total() }} orders
            </small>
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

@endsection
