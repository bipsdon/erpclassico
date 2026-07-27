@extends('layouts.app')

@section('title', 'Delivery Dashboard')
@section('page-title')
    <i class="bi bi-truck me-2" style="color:#dc3545"></i>Delivery
@endsection

@push('styles')
<style>
    :root { --del-red: #dc3545; --del-red-light: #fff5f5; --del-red-mid: #f8d7da; }

    .del-stat { background:#fff; border-radius:.5rem; border:1px solid #f0f0f0; padding:.9rem 1.1rem; display:flex; align-items:center; gap:.8rem; }
    .del-stat-icon { width:38px;height:38px;border-radius:.4rem;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0; }
    .del-stat-num  { font-size:1.5rem;font-weight:700;line-height:1; }
    .del-stat-lbl  { font-size:.7rem;color:#6c757d;text-transform:uppercase;letter-spacing:.4px; }

    .del-card { background:#fff; border-radius:.5rem; border:1px solid #e9ecef; border-left:4px solid #e9ecef; transition:box-shadow .15s; }
    .del-card:hover { box-shadow:0 .25rem .7rem rgba(0,0,0,.08); }
    .del-card.urgent { border-left-color:var(--del-red); }
    .del-card.high   { border-left-color:#ffc107; }
    .del-card.normal { border-left-color:#198754; }

    .del-section { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--del-red);margin:1.4rem 0 .7rem;display:flex;align-items:center;gap:.5rem; }
    .del-section::after { content:'';flex:1;height:1px;background:var(--del-red-mid); }

    .challan-saved  { background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.35rem;padding:.35rem .6rem;font-size:.78rem;color:#166534;display:flex;align-items:center;gap:.4rem; }
    .challan-missing{ background:var(--del-red-light);border:1px solid var(--del-red-mid);border-radius:.35rem;padding:.35rem .6rem;font-size:.78rem;color:#842029;display:flex;align-items:center;gap:.4rem; }

    .del-action-bar { border-top:1px solid #f0f0f0;padding:.65rem 1rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;background:#fafafa;border-radius:0 0 .5rem .5rem; }

    .del-table th { font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:#6c757d; }
    .del-table td { vertical-align:middle;font-size:.85rem; }
</style>
@endpush

@section('content')

{{-- ── Stats ───────────────────────────────────────────────── --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#fff5f5;color:var(--del-red)"><i class="bi bi-box-seam"></i></div>
            <div><div class="del-stat-num" style="color:var(--del-red)">{{ $readyOrders->count() }}</div><div class="del-stat-lbl">Awaiting</div></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#e7f5ff;color:#0d6efd"><i class="bi bi-truck"></i></div>
            <div><div class="del-stat-num" style="color:#0d6efd">{{ $deliveredToday }}</div><div class="del-stat-lbl">Today</div></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-calendar-week"></i></div>
            <div><div class="del-stat-num" style="color:#198754">{{ $deliveredThisWeek }}</div><div class="del-stat-lbl">This Week</div></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#f3f4f6;color:#6c757d"><i class="bi bi-calendar-month"></i></div>
            <div><div class="del-stat-num" style="color:#6c757d">{{ $deliveredThisMonth }}</div><div class="del-stat-lbl">This Month</div></div>
        </div>
    </div>
</div>

{{-- ── Ready for Delivery ───────────────────────────────────── --}}
<div class="del-section">
    <i class="bi bi-box-seam"></i>Ready for Delivery
    <span class="badge ms-1" style="background:var(--del-red);font-size:.62rem">{{ $readyOrders->count() }}</span>
</div>

@if($readyOrders->isEmpty())
    <div class="del-stat mb-4 justify-content-center text-muted py-4 shadow-sm">
        <i class="bi bi-check-circle text-success fs-5"></i>
        All clear — no orders waiting for delivery.
    </div>
@else
    <div class="row g-3 mb-2">
        @foreach($readyOrders as $order)
            @php
                $dp            = $order->delivery_priority; // urgent/high/normal
                $days          = $order->days_remaining;
                $hasChallan    = (bool) $order->challan_number;
                $isDeliveryIncharge = auth()->user()->isDeliveryIncharge();
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="del-card {{ $dp }} shadow-sm">

                    {{-- Card body --}}
                    <div class="p-3">

                        {{-- Ref + priority --}}
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <a href="{{ route('orders.show', $order) }}"
                               class="fw-bold text-decoration-none text-dark lh-sm">
                                @if($order->whatsapp_order_id)
                                    <i class="bi bi-whatsapp text-success" style="font-size:.8rem"></i>
                                    {{ $order->whatsapp_order_id }}
                                @else
                                    {{ $order->order_number }}
                                @endif
                                <div class="text-muted fw-normal" style="font-size:.68rem">{{ $order->order_number }}</div>
                            </a>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <span class="badge" style="font-size:.65rem;background:{{ $dp==='urgent'?'var(--del-red)':($dp==='high'?'#ffc107':'#198754') }};color:{{ $dp==='high'?'#000':'#fff' }}">
                                    {{ ucfirst($dp) }}
                                </span>
                                <span class="badge bg-{{ $order->priority_badge }}" style="font-size:.65rem">{{ ucfirst($order->priority) }}</span>
                            </div>
                        </div>

                        {{-- Customer + qty --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <x-order-avatar :url="$order->profile_picture_url" :initials="$order->avatar_initials" :size="32"/>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate" style="font-size:.85rem">{{ $order->customer_name }}</div>
                                <div class="text-muted" style="font-size:.7rem">{{ $order->customer_phone }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold lh-1" style="font-size:1.15rem;color:var(--del-red)">{{ number_format($order->quantity) }}</div>
                                <div class="text-muted" style="font-size:.68rem">{{ $order->product_type_label }}</div>
                            </div>
                        </div>

                        {{-- Date + method chips --}}
                        <div class="d-flex gap-1 flex-wrap mb-2" style="font-size:.75rem">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-calendar3 me-1"></i>{{ $order->delivery_date->format('d M Y') }}
                                @if($days < 0)·<span style="color:var(--del-red)">{{ abs($days) }}d overdue</span>
                                @elseif($days === 0)·<span class="text-warning">Today</span>
                                @else·{{ $days }}d left
                                @endif
                            </span>
                            @if($order->delivery_method)
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-send me-1"></i>{{ $order->delivery_method_label }}
                                </span>
                            @endif
                        </div>

                        {{-- Delivery instructions (if set) --}}
                        @if($order->delivery_details)
                            <div class="mb-2 text-muted" style="font-size:.78rem;white-space:pre-wrap;background:#f8f9fa;border-radius:.3rem;padding:.4rem .6rem">{{ $order->delivery_details }}</div>
                        @endif

                        {{-- Challan status --}}
                        @if($hasChallan)
                            <div class="challan-saved mb-2">
                                <i class="bi bi-upc-scan"></i>
                                Challan: <strong>{{ $order->challan_number }}</strong>
                                {{-- allow re-saving if needed --}}
                                <a href="#" class="ms-auto text-muted" style="font-size:.7rem"
                                   data-bs-toggle="collapse"
                                   data-bs-target="#edit-challan-{{ $order->id }}">edit</a>
                            </div>
                            <div class="collapse mb-2" id="edit-challan-{{ $order->id }}">
                                <form method="POST" action="{{ route('production.save-challan', $order) }}" class="d-flex gap-2">
                                    @csrf @method('PATCH')
                                    <input type="text" name="challan_number" class="form-control form-control-sm" value="{{ $order->challan_number }}" required>
                                    <button class="btn btn-sm btn-outline-secondary flex-shrink-0">Save</button>
                                </form>
                            </div>
                        @else
                            <div class="challan-missing mb-2">
                                <i class="bi bi-exclamation-circle"></i>
                                No challan saved yet
                            </div>
                        @endif

                        {{-- PM delivery info editor --}}
                        @if(auth()->user()->isPipelineManager())
                            <a href="#" class="text-muted d-inline-flex align-items-center gap-1 mt-1"
                               style="font-size:.72rem;text-decoration:none"
                               data-bs-toggle="collapse"
                               data-bs-target="#del-info-{{ $order->id }}">
                                <i class="bi bi-pencil" style="font-size:.68rem"></i> Edit delivery info
                            </a>
                            <div class="collapse mt-2" id="del-info-{{ $order->id }}">
                                <form method="POST" action="{{ route('production.delivery-info', $order) }}">
                                    @csrf @method('PATCH')
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <select name="delivery_method" class="form-select form-select-sm">
                                                <option value="">— Delivery method —</option>
                                                @foreach(['pathao'=>'Pathao','company_delivery'=>'Company Delivery','bus_ma_haldine'=>'Bus Ma Haldine','customer_pickup'=>'Customer Pickup','ncm'=>'NCM'] as $v=>$l)
                                                    <option value="{{ $v }}" {{ $order->delivery_method===$v?'selected':'' }}>{{ $l }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <textarea name="delivery_details" class="form-control form-control-sm" rows="2" placeholder="Address, contact…">{{ $order->delivery_details }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                                <i class="bi bi-save me-1"></i>Save info
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>

                    {{-- Action bar --}}
                    <div class="del-action-bar">

                        {{-- Save challan (only shown when no challan yet) --}}
                        @if(! $hasChallan)
                            <form method="POST"
                                  action="{{ route('production.save-challan', $order) }}"
                                  class="d-flex gap-1 flex-grow-1">
                                @csrf @method('PATCH')
                                <input type="text"
                                       name="challan_number"
                                       class="form-control form-control-sm"
                                       placeholder="Challan number"
                                       required>
                                <button type="submit" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                    <i class="bi bi-save me-1"></i>Save
                                </button>
                            </form>
                        @endif

                        {{-- Mark Delivered button --}}
                        @if($hasChallan || auth()->user()->isPipelineManager())
                            {{-- Challan already saved OR PM (no challan required) → direct submit --}}
                            <form method="POST"
                                  action="{{ route('production.deliver', $order) }}"
                                  onsubmit="return confirm('Mark {{ addslashes($order->whatsapp_order_id ?? $order->order_number) }} as delivered?')"
                                  class="{{ $hasChallan ? 'flex-grow-1' : '' }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="btn btn-sm text-white w-100"
                                        style="background:var(--del-red)">
                                    <i class="bi bi-truck me-1"></i>Mark Delivered
                                </button>
                            </form>
                        @else
                            {{-- Delivery incharge, no challan → open modal to enter challan + deliver at once --}}
                            <button type="button"
                                    class="btn btn-sm text-white flex-shrink-0"
                                    style="background:var(--del-red)"
                                    data-bs-toggle="modal"
                                    data-bs-target="#challanModal"
                                    data-order-id="{{ $order->id }}"
                                    data-order-ref="{{ addslashes($order->whatsapp_order_id ?? $order->order_number) }}"
                                    data-action="{{ route('production.deliver', $order) }}">
                                <i class="bi bi-truck me-1"></i>Mark Delivered
                            </button>
                        @endif

                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Recent Deliveries ────────────────────────────────────── --}}
<div class="del-section mt-4">
    <i class="bi bi-clock-history"></i>Recent Deliveries
    <span class="text-muted fw-normal ms-1" style="font-size:.68rem;text-transform:none;letter-spacing:0">last 30 days</span>
</div>

<div class="card border-0 shadow-sm">
    @if($recentDeliveries->isEmpty())
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            No deliveries in the last 30 days.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 del-table" data-sortable>
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 sort-th" data-col="0">Order</th>
                        <th class="sort-th" data-col="1">Customer</th>
                        <th class="text-center sort-th" data-col="2">Qty</th>
                        <th class="text-center sort-th" data-col="3">Method</th>
                        <th class="text-center sort-th" data-col="4">Challan</th>
                        <th class="text-center sort-th" data-col="5">Due Date</th>
                        <th class="text-center sort-th" data-col="6">Result</th>
                        <th class="text-center pe-3 sort-th" data-col="7">Delivered At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentDeliveries as $order)
                        <tr>
                            <td class="ps-3" data-val="{{ $order->whatsapp_order_id ?? $order->order_number }}">
                                <a href="{{ route('orders.show', $order) }}"
                                   class="fw-semibold text-decoration-none text-dark">
                                    {{ $order->whatsapp_order_id ?? $order->order_number }}
                                </a>
                                @if($order->whatsapp_order_id)
                                    <div class="text-muted" style="font-size:.68rem">{{ $order->order_number }}</div>
                                @endif
                            </td>
                            <td data-val="{{ $order->customer_name }}">
                                <div class="d-flex align-items-center gap-2">
                                    <x-order-avatar :url="$order->profile_picture_url" :initials="$order->avatar_initials" :size="26"/>
                                    {{ $order->customer_name }}
                                </div>
                            </td>
                            <td class="text-center fw-semibold" data-val="{{ $order->quantity }}">{{ number_format($order->quantity) }}</td>
                            <td class="text-center" data-val="{{ $order->delivery_method ?? '' }}">
                                @if($order->delivery_method)
                                    <span class="badge bg-light text-dark border" style="font-size:.68rem">{{ $order->delivery_method_label }}</span>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td class="text-center" data-val="{{ $order->challan_number ?? '' }}">
                                @if($order->challan_number)
                                    <span class="badge bg-light text-dark border" style="font-size:.68rem">{{ $order->challan_number }}</span>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td class="text-center" data-val="{{ $order->delivery_date->toDateString() }}">
                                {{ $order->delivery_date->format('d M Y') }}
                            </td>
                            <td class="text-center" data-val="{{ $order->was_delivered_late ? 1 : 0 }}">
                                @if($order->was_delivered_late)
                                    <span class="badge" style="background:var(--del-red);font-size:.68rem">{{ $order->days_delivered_late }}d late</span>
                                @else
                                    <span class="badge bg-success" style="font-size:.68rem">On Time</span>
                                @endif
                            </td>
                            <td class="text-center pe-3" data-val="{{ $order->delivered_at ? $order->delivered_at->toDateString() : '' }}">
                                @if($order->delivered_at)
                                    {{ $order->delivered_at->format('d M Y') }}
                                    <div class="text-muted" style="font-size:.68rem">{{ $order->delivered_at->format('H:i') }}</div>
                                @else <span class="text-muted">—</span> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Challan + Deliver modal (when no challan saved yet) ───── --}}
<div class="modal fade" id="challanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold">
                    <i class="bi bi-upc-scan me-2" style="color:var(--del-red)"></i>Enter Challan Number
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="challanDeliverForm" method="POST" action="">
                @csrf @method('PATCH')
                <div class="modal-body pt-1">
                    <p class="text-muted mb-3" style="font-size:.82rem" id="challanModalDesc"></p>
                    <input type="text"
                           name="challan_number"
                           id="challanModalInput"
                           class="form-control"
                           placeholder="e.g. CH-2026-001"
                           required>
                    <div class="invalid-feedback">Challan number is required.</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white" style="background:var(--del-red)">
                        <i class="bi bi-truck me-1"></i>Deliver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('challanModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('challanDeliverForm').action = btn.dataset.action;
        document.getElementById('challanModalDesc').textContent =
            'Order ' + btn.dataset.orderRef + ' needs a challan number before it can be marked as delivered.';
        const input = document.getElementById('challanModalInput');
        input.value = '';
        input.classList.remove('is-invalid');
    });

    document.getElementById('challanDeliverForm').addEventListener('submit', function (e) {
        const input = document.getElementById('challanModalInput');
        if (!input.value.trim()) {
            e.preventDefault();
            input.classList.add('is-invalid');
            input.focus();
        }
    });
});
</script>
@endpush
