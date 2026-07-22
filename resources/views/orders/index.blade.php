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
                           placeholder="WhatsApp ID, order #, customer…"
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
                    <div class="col-auto ms-auto d-flex gap-2">
                        <a href="{{ route('orders.export.all-xlsx') }}?{{ http_build_query(request()->only('stage','priority','status','search')) }}"
                           class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download XLSX
                        </a>
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
@php
    /**
     * Helper: build the URL for a sortable column header.
     * Clicking the active column toggles asc/desc.
     * Clicking a different column always starts asc.
     */
    $sortUrl = function (string $key) use ($sortKey, $sortDir): string {
        $newDir = ($sortKey === $key && $sortDir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $newDir, 'page' => 1]);
    };

    $sortIcon = function (string $key) use ($sortKey, $sortDir): string {
        if ($sortKey !== $key) return 'bi-arrow-down-up text-muted opacity-50';
        return $sortDir === 'asc' ? 'bi-sort-down-alt text-primary' : 'bi-sort-up text-primary';
    };
@endphp

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 queue-table">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:1%">
                        {{-- Default sort --}}
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'default', 'dir' => 'asc', 'page' => 1]) }}"
                           class="text-decoration-none text-muted"
                           title="Reset to default sort (priority + delivery date)">
                            <i class="bi bi-arrow-counterclockwise {{ $sortKey === 'default' ? 'text-primary' : 'opacity-50' }}" style="font-size:.85rem"></i>
                        </a>
                    </th>
                    <th>
                        <a href="{{ $sortUrl('order_number') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                            Order # <i class="bi {{ $sortIcon('order_number') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
                    <th>
                        <a href="{{ $sortUrl('customer') }}" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1">
                            Customer <i class="bi {{ $sortIcon('customer') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="{{ $sortUrl('quantity') }}" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1">
                            Qty <i class="bi {{ $sortIcon('quantity') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="{{ $sortUrl('product_type') }}" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1">
                            Product <i class="bi {{ $sortIcon('product_type') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="{{ $sortUrl('stage') }}" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1">
                            Stage <i class="bi {{ $sortIcon('stage') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="{{ $sortUrl('priority') }}" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1">
                            Priority <i class="bi {{ $sortIcon('priority') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="{{ $sortUrl('status') }}" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1">
                            Status <i class="bi {{ $sortIcon('status') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
                    <th class="text-center">
                        <a href="{{ $sortUrl('delivery_date') }}" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1">
                            Delivery <i class="bi {{ $sortIcon('delivery_date') }}" style="font-size:.75rem"></i>
                        </a>
                    </th>
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
                                {{ $order->whatsapp_order_id ?? $order->order_number }}
                            </a>
                            @if($order->whatsapp_order_id)
                                <div class="text-muted" style="font-size:.72rem">{{ $order->order_number }}</div>
                            @endif
                        </td>

                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <x-order-avatar
                                    :url="$order->profile_picture_url"
                                    :initials="$order->avatar_initials"
                                    :size="32"
                                />
                                <div>
                                    <div style="font-size:.875rem">{{ $order->customer_name }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $order->customer_phone }}</div>
                                </div>
                            </div>
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
                            @if($order->stage === 'delivered')
                                @if($order->was_delivered_late)
                                    <span class="days-chip bg-danger text-white">Delivered {{ $order->days_delivered_late }}d late</span>
                                @else
                                    <span class="days-chip bg-success text-white">Delivered on time</span>
                                @endif
                            @elseif($order->stage === 'ready')
                                <span class="days-chip bg-info text-white">Ready</span>
                            @elseif($order->days_remaining < 0)
                                <span class="days-chip bg-danger text-white">{{ abs($order->days_remaining) }}d late</span>
                            @elseif($order->days_remaining === 0)
                                <span class="days-chip bg-warning text-dark">Today</span>
                            @else
                                <span class="days-chip bg-light border text-secondary">{{ $order->days_remaining }}d</span>
                            @endif
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
                                        @if($order->stage === 'ready')
                                            <form method="POST"
                                                  action="{{ route('production.deliver', $order) }}"
                                                  onsubmit="return confirm('Mark {{ $order->whatsapp_order_id ?? $order->order_number }} as delivered?')">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-success" title="Mark as Delivered">
                                                    <i class="bi bi-truck"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('orders.edit', $order) }}"
                                           class="btn btn-outline-primary"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST"
                                              action="{{ route('orders.destroy', $order) }}"
                                              onsubmit="return confirm('Delete order {{ $order->whatsapp_order_id ?? $order->order_number }}? This cannot be undone.')">
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
