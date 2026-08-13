@extends('layouts.app')

@section('title', 'Deleted Orders')
@section('page-title')
    <i class="bi bi-trash3 me-2 text-danger"></i>Deleted Orders
@endsection

@section('content')

{{-- ── Toolbar ───────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('orders.trash') }}"
              class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="WhatsApp ID, order #, customer…"
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Search
                </button>
                @if(request('search'))
                    <a href="{{ route('orders.trash') }}" class="btn btn-sm btn-outline-secondary ms-1">
                        <i class="bi bi-x-lg me-1"></i>Clear
                    </a>
                @endif
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Orders
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ── Info banner ───────────────────────────────────────── --}}
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3" style="font-size:.85rem">
    <i class="bi bi-info-circle-fill flex-shrink-0"></i>
    <span>
        Deleted orders are kept here and can be restored at any time.
        <strong>Permanently deleting removes all data and attachments and cannot be undone.</strong>
    </span>
</div>

{{-- ── Table ────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    @if($orders->isEmpty())
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-trash3 fs-2 d-block mb-2 text-success"></i>
            Trash is empty — no deleted orders.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 queue-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:180px">Order</th>
                        <th>Customer</th>
                        <th class="text-center d-none d-md-table-cell">Product</th>
                        <th class="text-center d-none d-md-table-cell">Qty</th>
                        <th class="text-center d-none d-sm-table-cell">Stage</th>
                        <th class="text-center d-none d-sm-table-cell">Priority</th>
                        <th class="text-center d-none d-lg-table-cell">Delivery Date</th>
                        <th class="text-center">Deleted</th>
                        <th class="text-center d-none d-sm-table-cell">Deleted By</th>
                        <th class="text-end pe-3" style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            {{-- Order ref --}}
                            <td class="ps-3">
                                <div class="fw-semibold text-dark" style="font-size:.85rem">
                                    {{ $order->whatsapp_order_id ?? $order->order_number }}
                                </div>
                                @if($order->whatsapp_order_id)
                                    <div class="text-muted" style="font-size:.7rem">{{ $order->order_number }}</div>
                                @endif
                            </td>

                            {{-- Customer --}}
                            <td>
                                <div style="font-size:.85rem">{{ $order->customer_name }}</div>
                                @if($order->customer_phone)
                                    <div class="text-muted" style="font-size:.7rem">{{ $order->customer_phone }}</div>
                                @endif
                            </td>

                            {{-- Product --}}
                            <td class="text-center d-none d-md-table-cell">
                                <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                    {{ $order->product_type_label }}
                                </span>
                            </td>

                            {{-- Qty --}}
                            <td class="text-center d-none d-md-table-cell fw-semibold" style="font-size:.85rem">
                                {{ number_format($order->quantity) }}
                            </td>

                            {{-- Stage --}}
                            <td class="text-center d-none d-sm-table-cell">
                                <span class="badge bg-secondary" style="font-size:.72rem">
                                    {{ $order->stage_label }}
                                </span>
                            </td>

                            {{-- Priority --}}
                            <td class="text-center d-none d-sm-table-cell">
                                <span class="badge bg-{{ $order->priority_badge }}" style="font-size:.72rem">
                                    {{ ucfirst($order->priority) }}
                                </span>
                            </td>

                            {{-- Delivery date --}}
                            <td class="text-center d-none d-lg-table-cell" style="font-size:.82rem">
                                {{ $order->delivery_date->format('d M Y') }}
                            </td>

                            {{-- Deleted at --}}
                            <td class="text-center" style="font-size:.78rem">
                                <span class="text-danger fw-semibold">
                                    {{ $order->deleted_at->format('d M Y') }}
                                </span>
                                <div class="text-muted" style="font-size:.68rem">
                                    {{ $order->deleted_at->diffForHumans() }}
                                </div>
                            </td>

                            {{-- Deleted by (creator is the closest proxy — actual deleter not stored) --}}
                            <td class="text-center d-none d-sm-table-cell" style="font-size:.78rem">
                                {{ $order->creator?->name ?? '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="text-end pe-3">
                                <div class="d-flex justify-content-end gap-1">
                                    {{-- Restore --}}
                                    <form method="POST"
                                          action="{{ route('orders.restore', $order->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-sm btn-success"
                                                title="Restore order">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                        </button>
                                    </form>

                                    {{-- Permanently delete --}}
                                    <form method="POST"
                                          action="{{ route('orders.force-delete', $order->id) }}"
                                          onsubmit="return confirm('Permanently delete order {{ addslashes($order->whatsapp_order_id ?? $order->order_number) }}? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Permanently delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center py-2 px-3">
                <div class="text-muted" style="font-size:.8rem">
                    Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} deleted orders
                </div>
                {{ $orders->withQueryString()->links() }}
            </div>
        @else
            <div class="card-footer bg-transparent border-top-0 py-2 px-3">
                <span class="text-muted" style="font-size:.8rem">
                    {{ $orders->total() }} deleted {{ Str::plural('order', $orders->total()) }}
                </span>
            </div>
        @endif
    @endif
</div>

@endsection
