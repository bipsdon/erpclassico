{{--
    Reusable capacity card for printing / sewing.
    Variables:
        $queue      DepartmentQueueDTO
        $icon       string  bi-* class
        $iconColor  string  Bootstrap text-* class (optional)
        $iconStyle  string  inline style (optional)
        $label      string
--}}
@php
    $iconColor ??= '';
    $iconStyle ??= '';
    $loadPct    = $queue->loadPercent();
@endphp

<div class="card shadow-sm border-0 h-100">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi {{ $icon }} fs-4 {{ $iconColor }}" @if($iconStyle) style="{{ $iconStyle }}" @endif></i>
            <span class="fw-semibold">{{ $label }}</span>
            @if($queue->hasOvertime())
                <span class="badge bg-warning text-dark ms-auto">+{{ $queue->overtimePercent() }}% OT</span>
            @else
                <span class="badge bg-success bg-opacity-10 text-success ms-auto border border-success">Normal</span>
            @endif
        </div>

        {{-- Workload number --}}
        <div class="d-flex justify-content-between align-items-end mb-2">
            <div>
                <div class="text-muted small">Today's Workload</div>
                <div class="fw-bold fs-4 text-{{ $queue->utilisationColour() }}">
                    {{ $loadPct }}%
                </div>
                <div class="text-muted" style="font-size:.75rem">of a working day</div>
            </div>
            <div class="text-end">
                <div class="text-muted small">Units Today</div>
                <div class="fw-bold fs-5">{{ number_format($queue->totalUnits()) }}</div>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="progress mb-1" style="height:10px;border-radius:5px">
            <div class="progress-bar bg-{{ $queue->utilisationColour() }}"
                 style="width:{{ $queue->normalBarWidth() }}%"></div>
            @if($queue->hasOvertime())
                <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated"
                     style="width:{{ $queue->overtimeBarWidth() }}%"></div>
            @endif
        </div>
        <div class="d-flex justify-content-between mb-3" style="font-size:.72rem;color:#adb5bd">
            <span>0%</span><span>100% cap</span>
        </div>

        {{-- Product breakdown --}}
        @if(!empty($queue->unitsByProductType))
            <div class="d-flex flex-wrap gap-1 mb-3">
                @foreach($queue->unitsByProductType as $type => $units)
                    @php $rate = \App\Models\CapacityConfig::rateFor($queue->department, $type); @endphp
                    <span class="badge bg-light text-secondary border" style="font-size:.72rem"
                          title="{{ $units }} units at {{ $rate }}/day = {{ round(($units/$rate)*100) }}% of day">
                        {{ $units }} {{ \App\Models\CapacityConfig::productTypes()[$type] ?? $type }}
                        <span class="opacity-75">({{ round(($units/$rate)*100) }}%)</span>
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Health summary --}}
        @php $hs = $queue->healthSummary(); @endphp
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-success">{{ $hs['green'] }} OK</span>
            <span class="badge bg-warning text-dark">{{ $hs['yellow'] }} At Risk</span>
            <span class="badge bg-danger">{{ $hs['red'] }} Critical</span>
        </div>
    </div>
</div>
