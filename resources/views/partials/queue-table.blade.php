{{--
    Reusable queue table partial.
    Variables:
        $queue            DepartmentQueueDTO
        $title            string (optional)
        $icon             string Bootstrap icon class (optional)
        $showCompletedBtn bool (optional, default false)
--}}

@php
    $title            ??= $queue->departmentLabel() . ' Queue';
    $icon             ??= 'bi-list-task';
    $showCompletedBtn ??= false;
    $health           = $queue->healthSummary();
    $loadPct          = $queue->loadPercent();
@endphp

<div class="card shadow-sm border-0 h-100">
    {{-- Card header --}}
    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi {{ $icon }} text-primary fs-5"></i>
            <span class="fw-semibold">{{ $title }}</span>
            <span class="badge bg-primary rounded-pill ms-1">{{ count($queue->orders) }}</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
            @if($health['red'] > 0)
                <span class="badge bg-danger">{{ $health['red'] }} Critical</span>
            @endif
            @if($health['yellow'] > 0)
                <span class="badge bg-warning text-dark">{{ $health['yellow'] }} At Risk</span>
            @endif
            @if($health['green'] > 0)
                <span class="badge bg-success">{{ $health['green'] }} On Track</span>
            @endif
        </div>
    </div>

    {{-- Workload bar (capped depts only) --}}
    @if($loadPct !== null)
        <div class="card-body border-bottom py-2 px-3 capacity-bar">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted fw-semibold">
                    Workload:
                    <span class="fw-bold text-{{ $queue->utilisationColour() }}">
                        {{ $loadPct }}%
                    </span>
                    of a working day
                    @if(!empty($queue->unitsByProductType))
                        <span class="text-muted fw-normal ms-2">
                            ({{ collect($queue->unitsByProductType)->map(fn($qty, $type) => $qty . ' ' . (\App\Models\CapacityConfig::productTypes()[$type] ?? $type))->implode(', ') }})
                        </span>
                    @endif
                </small>
                @if($queue->hasOvertime())
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-plus-circle-fill me-1"></i>+{{ $queue->overtimePercent() }}% overtime
                    </span>
                @endif
            </div>
            {{-- Two-segment bar: normal (green) + overtime (striped warning) --}}
            <div class="progress" style="height:12px;border-radius:6px">
                <div class="progress-bar bg-{{ $queue->utilisationColour() }}"
                     role="progressbar"
                     style="width:{{ $queue->normalBarWidth() }}%"
                     title="{{ min(100, $loadPct) }}% of daily capacity">
                </div>
                @if($queue->hasOvertime())
                    <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                         role="progressbar"
                         style="width:{{ $queue->overtimeBarWidth() }}%"
                         title="+{{ $queue->overtimePercent() }}% overtime">
                    </div>
                @endif
            </div>
            <div class="d-flex justify-content-between mt-1" style="font-size:.7rem;color:#adb5bd">
                <span>0%</span>
                <span>100% (full day)</span>
            </div>
        </div>
    @endif

    {{-- Overtime warning banner --}}
    @if($queue->hasOvertime())
        <div class="card-body overtime-alert py-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-alarm-fill text-danger fs-5 flex-shrink-0"></i>
                <div>
                    <div class="fw-bold text-danger" style="font-size:.82rem">
                        {{ strtoupper($queue->departmentLabel()) }} OVERTIME REQUIRED
                    </div>
                    <div class="text-muted" style="font-size:.78rem">
                        {{ $queue->overtimePercent() }}% beyond full-day capacity
                        (Total workload: {{ $queue->loadPercent() }}% of a working day)
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Orders table --}}
    <div class="card-body p-0">
        @if(count($queue->orders) === 0)
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No orders scheduled for today
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 queue-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:1%">#</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th class="text-center">Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Load</th>
                            <th class="text-center">Priority</th>
                            <th class="text-center">Delivery</th>
                            <th class="text-center">Status</th>
                            @if($showCompletedBtn)
                                <th class="text-center pe-3">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($queue->orders as $i => $order)
                            <tr class="{{ $order->isLate ? 'table-danger' : '' }}">
                                <td class="ps-3 text-muted" style="font-size:.8rem">{{ $i + 1 }}</td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="health-dot bg-{{ $order->healthBadge() }}"></span>
                                        <div>
                                            <a href="{{ route('orders.show', $order->orderId) }}"
                                               class="fw-semibold text-decoration-none text-dark"
                                               style="font-size:.875rem">
                                                {{ $order->orderNumber }}
                                            </a>
                                            @if($order->isOvertime)
                                                <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">OT</span>
                                            @endif
                                            @if($order->whatsappOrderId)
                                                <div class="text-muted mt-1" style="font-size:.72rem">
                                                    <i class="bi bi-whatsapp text-success me-1"></i>{{ $order->whatsappOrderId }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td style="font-size:.875rem">{{ $order->customerName }}</td>

                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border" style="font-size:.72rem">
                                        {{ $order->productTypeLabel }}
                                    </span>
                                </td>

                                <td class="text-center fw-semibold">{{ number_format($order->quantity) }}</td>

                                <td class="text-center">
                                    @if($order->dayFraction > 0)
                                        <span class="badge bg-{{ $order->dayPercent() > 100 ? 'warning text-dark' : 'light text-secondary border' }}"
                                              style="font-size:.72rem">
                                            {{ $order->dayPercent() }}%
                                        </span>
                                    @else
                                        <span class="text-muted" style="font-size:.8rem">—</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-{{ $order->priorityBadge() }}">
                                        {{ ucfirst($order->priority) }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <div style="font-size:.8rem">
                                        {{ \Carbon\Carbon::parse($order->deliveryDate)->format('d M') }}
                                    </div>
                                    <span class="days-chip ms-1
                                        @if($order->daysUntilDelivery < 0) bg-danger text-white
                                        @elseif($order->daysUntilDelivery <= 1) bg-warning text-dark
                                        @else bg-light text-secondary border
                                        @endif">
                                        @if($order->daysUntilDelivery < 0)
                                            {{ abs($order->daysUntilDelivery) }}d late
                                        @elseif($order->daysUntilDelivery === 0)
                                            Today
                                        @else
                                            {{ $order->daysUntilDelivery }}d
                                        @endif
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-{{ $order->healthBadge() }}">
                                        {{ ucfirst($order->healthStatus) }}
                                    </span>
                                </td>

                                @if($showCompletedBtn)
                                    <td class="text-center pe-3">
                                        <form method="POST"
                                              action="{{ route('production.complete', ['department' => $order->department, 'orderId' => $order->orderId]) }}"
                                              onsubmit="return confirm('Mark {{ $order->orderNumber }} as completed in {{ $queue->departmentLabel() }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check2"></i> Done
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card-footer bg-white text-muted d-flex justify-content-between align-items-center"
         style="font-size:.78rem">
        <span>
            <i class="bi bi-calendar3 me-1"></i>
            {{ \Carbon\Carbon::parse($queue->date)->format('l, d F Y') }}
        </span>
        <span>
            {{ count($queue->orders) }} order(s) ·
            <strong>{{ number_format($queue->totalUnits()) }}</strong> units
        </span>
    </div>
</div>
