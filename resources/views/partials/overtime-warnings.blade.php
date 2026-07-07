{{-- Variable: $plan DailyProductionPlanDTO --}}

@if($plan->hasAnyOvertime())
    <div class="mb-4">
        <div class="section-title">
            <i class="bi bi-alarm-fill text-danger me-2"></i>Overtime Warnings
        </div>
        <div class="row g-3">
            @foreach($plan->overtimeDepartments() as $dept)
                <div class="col-12 col-md-6">
                    <div class="alert alert-danger overtime-alert d-flex gap-3 mb-0 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-octagon-fill fs-4 flex-shrink-0 pt-1"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold mb-1">
                                {{ strtoupper($dept->departmentLabel()) }} OVERTIME REQUIRED
                            </div>
                            <div class="d-flex flex-wrap gap-3 mb-2">
                                <span>
                                    <span class="text-muted small">Workload:</span>
                                    <strong>{{ $dept->loadPercent() }}%</strong> of a day
                                </span>
                                <span class="text-danger fw-semibold">
                                    <i class="bi bi-plus-circle-fill me-1"></i>
                                    <strong>{{ $dept->overtimePercent() }}%</strong> overtime
                                </span>
                            </div>
                            {{-- Product type breakdown --}}
                            @if(!empty($dept->unitsByProductType))
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach($dept->unitsByProductType as $type => $units)
                                        <span class="badge bg-white text-dark border">
                                            {{ $units }}
                                            {{ \App\Models\CapacityConfig::productTypes()[$type] ?? $type }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            {{-- Progress bar --}}
                            <div class="progress" style="height:8px;border-radius:4px">
                                <div class="progress-bar bg-warning"
                                     style="width:{{ $dept->normalBarWidth() }}%"></div>
                                @if($dept->hasOvertime())
                                    <div class="progress-bar bg-danger progress-bar-striped"
                                         style="width:{{ $dept->overtimeBarWidth() }}%"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
