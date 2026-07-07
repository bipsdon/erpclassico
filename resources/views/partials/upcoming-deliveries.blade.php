{{--
    Upcoming deliveries widget.
    Variable: $upcomingOrders  Collection of Order models
--}}

<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-3">
        <i class="bi bi-truck text-primary fs-5"></i>
        <span class="fw-semibold">Upcoming Deliveries</span>
        <span class="badge bg-secondary rounded-pill ms-auto">{{ $upcomingOrders->count() }}</span>
    </div>
    <div class="card-body p-0">
        @forelse($upcomingOrders as $order)
            @php
                $days = (int) now()->startOfDay()->diffInDays($order->delivery_date, false);
            @endphp
            <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">

                {{-- Days countdown --}}
                <div class="text-center flex-shrink-0" style="width:44px">
                    <div class="fw-bold fs-5 lh-1
                        {{ $days < 0 ? 'text-danger' : ($days <= 1 ? 'text-warning' : 'text-primary') }}">
                        @if($days < 0)
                            <i class="bi bi-exclamation-triangle-fill" style="font-size:1.1rem"></i>
                        @else
                            {{ $days }}
                        @endif
                    </div>
                    <div class="text-muted" style="font-size:.63rem;text-transform:uppercase;letter-spacing:.5px">
                        @if($days < 0) LATE
                        @elseif($days === 0) TODAY
                        @elseif($days === 1) TMRW
                        @else DAYS
                        @endif
                    </div>
                </div>

                {{-- Order info --}}
                <div class="flex-grow-1 min-w-0">
                    <a href="{{ route('orders.show', $order) }}"
                       class="fw-semibold text-truncate d-block text-decoration-none text-dark"
                       style="font-size:.85rem">
                        {{ $order->customer_name }}
                    </a>
                    <div class="text-muted text-truncate" style="font-size:.75rem">
                        {{ $order->order_number }} &middot; {{ number_format($order->quantity) }} {{ $order->product_type_label }}
                    </div>
                </div>

                {{-- Badges --}}
                <div class="text-end flex-shrink-0">
                    <span class="badge bg-{{ $order->priority_badge }} mb-1 d-block">
                        {{ ucfirst($order->priority) }}
                    </span>
                    <span class="badge bg-light text-secondary border" style="font-size:.65rem">
                        {{ $order->stage_label }}
                    </span>
                </div>

            </div>
        @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-check fs-2 d-block mb-2"></i>
                No upcoming deliveries
            </div>
        @endforelse
    </div>
    <div class="card-footer bg-white text-muted" style="font-size:.78rem">
        Next 7 days · {{ $upcomingOrders->count() }} order(s)
    </div>
</div>
