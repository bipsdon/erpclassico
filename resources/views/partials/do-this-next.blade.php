{{--
    "Do This Next" banner for worker dashboards.
    Shows the top order from $queue with urgency messaging.
    Only renders when the queue has orders.

    Variable: $queue  DepartmentQueueDTO
--}}

@php
    $next = $queue->orders[0] ?? null;
@endphp

@if($next)
    @php
        $isUrgent = $next->daysUntilDelivery <= 0;
        $isAtRisk = $next->daysUntilDelivery === 1;

        if ($isUrgent) {
            $bannerBg    = '#dc3545';
            $bannerText  = '#fff';
            $icon        = 'bi-exclamation-triangle-fill';
            $label       = $next->isLate
                ? '⚠️ LATE — Start immediately'
                : '🔴 Due TODAY — Work on this first';
        } elseif ($isAtRisk || $next->healthStatus === 'yellow') {
            $bannerBg    = '#fff3cd';
            $bannerText  = '#856404';
            $icon        = 'bi-clock-fill';
            $label       = '🟡 Due tomorrow — This is your next priority';
        } else {
            $bannerBg    = '#d1e7dd';
            $bannerText  = '#0a3622';
            $icon        = 'bi-arrow-right-circle-fill';
            $label       = '✅ Work on this next';
        }
    @endphp

    <div class="rounded-3 shadow-sm mb-4 px-4 py-3 d-flex align-items-center gap-3 flex-wrap"
         style="background:{{ $bannerBg }};color:{{ $bannerText }};border-left:5px solid {{ $bannerText }}20">

        <i class="bi {{ $icon }} fs-2 flex-shrink-0"></i>

        <div class="flex-grow-1">
            <div class="fw-bold" style="font-size:1rem;letter-spacing:.2px">{{ $label }}</div>
            <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-semibold" style="font-size:1.05rem">
                    @if($next->whatsappOrderId)
                        <i class="bi bi-whatsapp me-1" style="color:{{ $isUrgent ? '#fff' : '#198754' }}"></i>{{ $next->whatsappOrderId }}
                    @else
                        {{ $next->orderNumber }}
                    @endif
                </span>
                <span style="opacity:.75">·</span>
                <span>{{ $next->customerName }}</span>
                <span style="opacity:.75">·</span>
                <span>{{ number_format($next->quantity) }} {{ $next->productTypeLabel }}</span>
                <span style="opacity:.75">·</span>
                <span class="fw-semibold">{{ $next->urgencyLabel() }}</span>
                @if($next->isPinned())
                    <span class="badge ms-1"
                          style="background:{{ $bannerText }}30;color:{{ $bannerText }};font-size:.68rem">
                        <i class="bi bi-pin-angle-fill me-1"></i>Pinned by PM
                    </span>
                @endif
            </div>
        </div>

        <a href="{{ route('orders.show', $next->orderId) }}"
           class="btn btn-sm flex-shrink-0"
           style="background:{{ $bannerText }}20;color:{{ $bannerText }};border:1px solid {{ $bannerText }}40">
            <i class="bi bi-arrow-right me-1"></i>Open Order
        </a>

    </div>
@endif
