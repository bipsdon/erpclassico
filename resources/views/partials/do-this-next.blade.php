{{--
    "Do This Next" banner for worker dashboards.
    Shows the top order from $queue with urgency messaging.
    Only renders when the queue has orders.

    Variable: $queue  DepartmentQueueDTO
--}}

@php
    // Skip orders already in progress (another designer is on them)
    // and find the first one still waiting to be picked up.
    $next = collect($queue->orders)->first(
        fn($o) => !in_array($o->orderStatus, ['in_progress', 'completed'])
    );
    $isDesign = $queue->department === 'design';
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

        // Brief status for design queue
        $briefMissing = $isDesign && $next->isMissingBrief();
    @endphp

    <div class="rounded-3 shadow-sm mb-3 px-4 py-3"
         style="background:{{ $bannerBg }};color:{{ $bannerText }};border-left:5px solid {{ $bannerText }}20">

        {{-- Top row: icon + order info + Open button --}}
        <div class="d-flex align-items-center gap-3 flex-wrap">

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

        {{-- Design-only: brief status row --}}
        @if($isDesign)
            <div class="mt-3 pt-2 d-flex align-items-start gap-3 flex-wrap"
                 style="border-top:1px solid {{ $bannerText }}25">

                {{-- Brief / file status --}}
                <div class="d-flex align-items-center gap-2 flex-wrap">

                    @if($briefMissing)
                        {{-- No brief at all — very prominent warning --}}
                        <span class="badge fs-6 px-2 py-1"
                              style="background:#fff;color:#dc3545;border:1.5px solid #dc3545">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>No brief or spec — ask PM before starting
                        </span>
                    @else
                        {{-- Files --}}
                        @if($next->attachmentsCount > 0)
                            <span class="badge"
                                  style="background:{{ $bannerText }}20;color:{{ $bannerText }};border:1px solid {{ $bannerText }}40;font-size:.8rem">
                                <i class="bi bi-paperclip me-1"></i>
                                {{ $next->attachmentsCount }} attached file{{ $next->attachmentsCount !== 1 ? 's' : '' }}
                            </span>
                        @else
                            <span class="badge"
                                  style="background:{{ $bannerText }}15;color:{{ $bannerText }};border:1px solid {{ $bannerText }}30;font-size:.8rem;opacity:.75">
                                <i class="bi bi-paperclip-fill me-1"></i>No files
                            </span>
                        @endif

                        {{-- Spec written --}}
                        @if($next->hasDetails)
                            <span class="badge"
                                  style="background:{{ $bannerText }}20;color:{{ $bannerText }};border:1px solid {{ $bannerText }}40;font-size:.8rem">
                                <i class="bi bi-file-text me-1"></i>Spec written
                            </span>
                        @else
                            <span class="badge"
                                  style="background:{{ $bannerText }}15;color:{{ $bannerText }};border:1px solid {{ $bannerText }}30;font-size:.8rem;opacity:.75">
                                <i class="bi bi-file-earmark me-1"></i>No spec
                            </span>
                        @endif

                        {{-- Internal notes from PM --}}
                        @if($next->hasNotes)
                            <span class="badge"
                                  style="background:#fff3cd;color:#856404;border:1px solid #f0c040;font-size:.8rem"
                                  title="Pipeline manager left internal notes on this order">
                                <i class="bi bi-sticky-fill me-1"></i>PM notes — check order
                            </span>
                        @endif
                    @endif

                </div>

            </div>
        @endif

    </div>
@endif
