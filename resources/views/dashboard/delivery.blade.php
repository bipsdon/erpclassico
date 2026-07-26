@extends('layouts.app')

@section('title', 'Delivery Dashboard')
@section('page-title')
    <i class="bi bi-truck me-2" style="color:#dc3545"></i>Delivery
@endsection

@push('styles')
<style>
    /* ── accent colour ───────────────── */
    :root { --del-red: #dc3545; --del-red-light: #fff5f5; --del-red-mid: #f8d7da; }

    /* ── stat strip ─────────────────── */
    .del-stat {
        background: #fff;
        border-radius: .5rem;
        border: 1px solid #f0f0f0;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: .9rem;
    }
    .del-stat-icon {
        width: 40px; height: 40px; border-radius: .4rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .del-stat-num { font-size: 1.6rem; font-weight: 700; line-height: 1; }
    .del-stat-lbl { font-size: .72rem; color: #6c757d; text-transform: uppercase; letter-spacing: .4px; }

    /* ── order cards ────────────────── */
    .del-card {
        background: #fff;
        border-radius: .5rem;
        border: 1px solid #e9ecef;
        border-left: 4px solid #e9ecef;
        transition: box-shadow .15s;
        overflow: hidden;
    }
    .del-card:hover { box-shadow: 0 .3rem .8rem rgba(0,0,0,.08); }
    .del-card.urgent { border-left-color: var(--del-red); }
    .del-card.high   { border-left-color: #ffc107; }
    .del-card.normal { border-left-color: #198754; }

    /* ── challan field ──────────────── */
    .challan-row { background: var(--del-red-light); border-top: 1px solid var(--del-red-mid); padding: .65rem 1rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .challan-row input { flex: 1; min-width: 140px; font-size: .85rem; }
    .challan-row .btn  { white-space: nowrap; }

    /* ── section label ──────────────── */
    .del-section { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--del-red); margin: 1.5rem 0 .75rem; display: flex; align-items: center; gap: .5rem; }
    .del-section::after { content: ''; flex: 1; height: 1px; background: var(--del-red-mid); }

    /* ── table tweaks ───────────────── */
    .del-table th { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: #6c757d; }
    .del-table td { vertical-align: middle; }
</style>
@endpush

@section('content')

{{-- ── Stat strip ──────────────────────────────────────── --}}
<div class="row g-2 mb-4">
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#fff5f5;color:var(--del-red)"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="del-stat-num" style="color:var(--del-red)">{{ $readyOrders->count() }}</div>
                <div class="del-stat-lbl">Awaiting</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#e7f5ff;color:#0d6efd"><i class="bi bi-truck"></i></div>
            <div>
                <div class="del-stat-num" style="color:#0d6efd">{{ $deliveredToday }}</div>
                <div class="del-stat-lbl">Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#e8f5e9;color:#198754"><i class="bi bi-calendar-week"></i></div>
            <div>
                <div class="del-stat-num" style="color:#198754">{{ $deliveredThisWeek }}</div>
                <div class="del-stat-lbl">This Week</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="del-stat shadow-sm">
            <div class="del-stat-icon" style="background:#f3f4f6;color:#6c757d"><i class="bi bi-calendar-month"></i></div>
            <div>
                <div class="del-stat-num" style="color:#6c757d">{{ $deliveredThisMonth }}</div>
                <div class="del-stat-lbl">This Month</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Ready orders ─────────────────────────────────────── --}}
<div class="del-section">
    <i class="bi bi-box-seam"></i>
    Ready for Delivery
    <span class="badge ms-1" style="background:var(--del-red);font-size:.65rem">{{ $readyOrders->count() }}</span>
</div>

@if($readyOrders->isEmpty())
    <div class="del-stat shadow-sm mb-4 justify-content-center text-muted py-4">
        <i class="bi bi-check-circle text-success me-2 fs-5"></i>
        All clear — no orders waiting for delivery.
    </div>
@else
    <div class="row g-3 mb-2">
        @foreach($readyOrders as $order)
            @php
                $dp   = $order->delivery_priority; // urgent/high/normal
                $days = $order->days_remaining;
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <div class="del-card {{ $dp }} shadow-sm">

                    {{-- Card body --}}
                    <div class="p-3">

                        {{-- Row 1: ref + badges --}}
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <a href="{{ route('orders.show', $order) }}"
                               class="fw-bold text-decoration-none text-dark lh-sm">
                                @if($order->whatsapp_order_id)
                                    <i class="bi bi-whatsapp text-success" style="font-size:.85rem"></i>
                                    {{ $order->whatsapp_order_id }}
                                @else
                                    {{ $order->order_number }}
                                @endif
                                <div class="text-muted fw-normal" style="font-size:.7rem">{{ $order->order_number }}</div>
                            </a>
                            <div class="d-flex gap-1 flex-shrink-0">
                                @if($dp === 'urgent')
                                    <span class="badge" style="background:var(--del-red);font-size:.68rem">Urgent</span>
                                @elseif($dp === 'high')
                                    <span class="badge bg-warning text-dark" style="font-size:.68rem">High</span>
                                @else
                                    <span class="badge bg-success" style="font-size:.68rem">Normal</span>
                                @endif
                                <span class="badge bg-{{ $order->priority_badge }}" style="font-size:.68rem">{{ ucfirst($order->priority) }}</span>
                            </div>
                        </div>

                        {{-- Row 2: customer + qty --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <x-order-avatar :url="$order->profile_picture_url" :initials="$order->avatar_initials" :size="32"/>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate" style="font-size:.85rem">{{ $order->customer_name }}</div>
                                <div class="text-muted" style="font-size:.72rem">{{ $order->customer_phone }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold" style="font-size:1.1rem;color:var(--del-red)">{{ number_format($order->quantity) }}</div>
                                <div class="text-muted" style="font-size:.7rem">{{ $order->product_type_label }}</div>
                            </div>
                        </div>

                        {{-- Row 3: date + method chips --}}
                        <div class="d-flex gap-1 flex-wrap" style="font-size:.75rem">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-calendar3 me-1"></i>{{ $order->delivery_date->format('d M') }}
                                @if($days < 0)
                                    · <span style="color:var(--del-red)">{{ abs($days) }}d overdue</span>
                                @elseif($days === 0)
                                    · <span class="text-warning">Today</span>
                                @else
                                    · {{ $days }}d left
                                @endif
                            </span>
                            @if($order->delivery_method)
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-send me-1"></i>{{ $order->delivery_method_label }}
                                </span>
                            @endif
                        </div>

                        @if($order->delivery_details)
                            <div class="mt-2 text-muted" style="font-size:.78rem;white-space:pre-wrap;background:#fafafa;border-radius:.3rem;padding:.4rem .6rem">{{ $order->delivery_details }}</div>
                        @endif

                        {{-- PM delivery info editor --}}
                        @if(auth()->user()->isPipelineManager())
                            <div class="mt-2">
                                <a class="text-muted d-inline-flex align-items-center gap-1"
                                   style="font-size:.73rem;cursor:pointer;text-decoration:none"
                                   data-bs-toggle="collapse"
                                   href="#del-info-{{ $order->id }}">
                                    <i class="bi bi-pencil" style="font-size:.7rem"></i> Edit delivery info
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
                                                    <i class="bi bi-save me-1"></i>Save
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Challan + deliver strip --}}
                    <form method="POST"
                          action="{{ route('production.deliver', $order) }}"
                          class="challan-row"
                          onsubmit="return confirmDeliver(this)">
                        @csrf @method('PATCH')
                        <i class="bi bi-upc-scan text-muted flex-shrink-0"></i>
                        <input type="text"
                               name="challan_number"
                               class="form-control form-control-sm challan-field"
                               placeholder="Challan number{{ auth()->user()->isDeliveryIncharge() ? ' (required)' : ' (optional)' }}"
                               {{ auth()->user()->isDeliveryIncharge() ? 'required' : '' }}>
                        <button type="submit" class="btn btn-sm text-white" style="background:var(--del-red)">
                            <i class="bi bi-truck me-1"></i>Deliver
                        </button>
                    </form>

                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ── Recent deliveries ────────────────────────────────── --}}
<div class="del-section mt-4">
    <i class="bi bi-clock-history"></i>
    Recent Deliveries
    <span class="text-muted fw-normal ms-1" style="font-size:.7rem;text-transform:none;letter-spacing:0">last 30 days</span>
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
                        <th class="text-center pe-3 sort-th" data-col="7">Delivered</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentDeliveries as $order)
                        @php
                            $deliveredAt = $order->delivered_at;
                            $wasLate     = $order->was_delivered_late;
                        @endphp
                        <tr>
                            <td class="ps-3" data-val="{{ $order->whatsapp_order_id ?? $order->order_number }}">
                                <a href="{{ route('orders.show', $order) }}"
                                   class="fw-semibold text-decoration-none text-dark" style="font-size:.85rem">
                                    {{ $order->whatsapp_order_id ?? $order->order_number }}
                                </a>
                                @if($order->whatsapp_order_id)
                                    <div class="text-muted" style="font-size:.7rem">{{ $order->order_number }}</div>
                                @endif
                            </td>
                            <td data-val="{{ $order->customer_name }}">
                                <div class="d-flex align-items-center gap-2">
                                    <x-order-avatar :url="$order->profile_picture_url" :initials="$order->avatar_initials" :size="26"/>
                                    <span style="font-size:.85rem">{{ $order->customer_name }}</span>
                                </div>
                            </td>
                            <td class="text-center fw-semibold" data-val="{{ $order->quantity }}" style="font-size:.85rem">
                                {{ number_format($order->quantity) }}
                            </td>
                            <td class="text-center" data-val="{{ $order->delivery_method ?? '' }}">
                                @if($order->delivery_method)
                                    <span class="badge bg-light text-dark border" style="font-size:.7rem">{{ $order->delivery_method_label }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center" data-val="{{ $order->challan_number ?? '' }}">
                                @if($order->challan_number)
                                    <span class="badge bg-light text-dark border" style="font-size:.7rem">{{ $order->challan_number }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center" data-val="{{ $order->delivery_date->toDateString() }}" style="font-size:.8rem">
                                {{ $order->delivery_date->format('d M Y') }}
                            </td>
                            <td class="text-center" data-val="{{ $wasLate ? 1 : 0 }}">
                                @if($wasLate)
                                    <span class="badge" style="background:var(--del-red);font-size:.7rem">{{ $order->days_delivered_late }}d late</span>
                                @else
                                    <span class="badge bg-success" style="font-size:.7rem">On Time</span>
                                @endif
                            </td>
                            <td class="text-center pe-3" data-val="{{ $deliveredAt ? $deliveredAt->toDateString() : '' }}">
                                @if($deliveredAt)
                                    <span style="font-size:.8rem">{{ $deliveredAt->format('d M Y') }}</span>
                                    <div class="text-muted" style="font-size:.7rem">{{ $deliveredAt->format('H:i') }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function confirmDeliver(form) {
    const isRequired = form.querySelector('.challan-field').required;
    const challan    = form.querySelector('.challan-field');
    if (isRequired && !challan.value.trim()) {
        challan.classList.add('is-invalid');
        challan.focus();
        // show inline message
        let fb = challan.nextElementSibling;
        if (!fb || !fb.classList.contains('invalid-feedback')) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            fb.textContent = 'Challan number is required.';
            challan.after(fb);
        }
        return false;
    }
    challan.classList.remove('is-invalid');
    const ref = form.closest('.del-card').querySelector('a.fw-bold')?.textContent?.trim() ?? 'this order';
    return confirm('Mark ' + ref.split('\n')[0].trim() + ' as delivered?');
}
</script>
@endpush
