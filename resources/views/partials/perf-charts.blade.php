{{--
    Performance visualisation partial — last 30 days.
    Requires: $perf (array from controller perfStats())
    Optional: $accentColor (CSS color string), $accentRgb ('r,g,b')
--}}
@php
    $accentColor  ??= '#0d6efd';
    $accentRgb    ??= '13,110,253';

    $totalJobs    = $perf['totalJobs'];
    $totalUnits   = $perf['totalUnits'];
    $overtimeDays = $perf['overtimeDays'];
    $activeDays   = $perf['activeDays'];
    $avgUnits     = $activeDays > 0 ? round($totalUnits / $activeDays) : 0;

    $productTypes = \App\Models\CapacityConfig::productTypes();
    $byProduct    = $perf['byProduct'];
    $byPriority   = $perf['byPriority'];

    $productLabelsJson = json_encode(array_values(
        array_map(fn($k) => $productTypes[$k] ?? ucfirst($k), array_keys($byProduct))
    ));
    $productDataJson   = json_encode(array_values($byProduct));
    $labelsJson        = json_encode($perf['labels']);
    $unitsJson         = json_encode($perf['units']);
    $overtimeJson      = json_encode($perf['overtime']);

    $priorityColors = [
        'critical' => '#dc3545',
        'rush'     => '#ffc107',
        'normal'   => '#6c757d',
    ];
@endphp

{{-- ── Section header ──────────────────────────────────────── --}}
<div class="section-title mt-2">
    <i class="bi bi-graph-up-arrow me-2" style="color:{{ $accentColor }}"></i>
    Performance — Last 30 Days
</div>

{{-- ── KPI strip ────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm h-100 text-center py-3">
            <div style="font-size:1.75rem;font-weight:700;color:{{ $accentColor }}">
                {{ number_format($totalJobs) }}
            </div>
            <div class="text-muted" style="font-size:.75rem">Orders Completed</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm h-100 text-center py-3">
            <div style="font-size:1.75rem;font-weight:700;color:{{ $accentColor }}">
                {{ number_format($totalUnits) }}
            </div>
            <div class="text-muted" style="font-size:.75rem">Units Processed</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm h-100 text-center py-3">
            <div style="font-size:1.75rem;font-weight:700;color:#198754">
                {{ number_format($avgUnits) }}
            </div>
            <div class="text-muted" style="font-size:.75rem">Avg Units / Active Day</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm h-100 text-center py-3">
            <div style="font-size:1.75rem;font-weight:700;color:{{ $overtimeDays > 0 ? '#ffc107' : '#198754' }}">
                {{ $overtimeDays }}
            </div>
            <div class="text-muted" style="font-size:.75rem">Overtime Days</div>
        </div>
    </div>
</div>

{{-- ── Charts row ───────────────────────────────────────────── --}}
<div class="row g-4 mb-4">

    {{-- 1. Daily throughput bar chart --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-semibold" style="font-size:.875rem">
                        <i class="bi bi-bar-chart-fill me-2" style="color:{{ $accentColor }}"></i>Daily Throughput
                    </span>
                    <span class="text-muted" style="font-size:.75rem">units completed per day</span>
                </div>
                <canvas id="perf-throughput-chart" height="110"></canvas>
            </div>
        </div>
    </div>

    {{-- 2. Product mix donut --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="fw-semibold mb-3" style="font-size:.875rem">
                    <i class="bi bi-pie-chart-fill me-2" style="color:{{ $accentColor }}"></i>Product Mix
                </div>
                @if(empty($byProduct))
                    <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted" style="font-size:.85rem">
                        <div class="text-center">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>No completed jobs yet
                        </div>
                    </div>
                @else
                    <canvas id="perf-product-chart" style="max-height:180px"></canvas>
                    <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                        @foreach($byProduct as $type => $units)
                            <div class="text-center" style="font-size:.72rem">
                                <div class="fw-semibold">{{ number_format($units) }}</div>
                                <div class="text-muted">{{ $productTypes[$type] ?? ucfirst($type) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Second row ───────────────────────────────────────────── --}}
<div class="row g-4 mb-4">

    {{-- 3. Priority breakdown --}}
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="fw-semibold mb-3" style="font-size:.875rem">
                    <i class="bi bi-flag-fill me-2 text-danger"></i>Completed Jobs by Priority
                </div>
                @if(empty($byPriority))
                    <div class="text-center text-muted py-4" style="font-size:.85rem">
                        <i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No completed jobs yet
                    </div>
                @else
                    @php $totalPri = array_sum($byPriority); @endphp
                    @foreach(['critical' => 'Critical', 'rush' => 'Rush', 'normal' => 'Normal'] as $key => $label)
                        @php
                            $count = $byPriority[$key] ?? 0;
                            $pct   = $totalPri > 0 ? round($count / $totalPri * 100) : 0;
                            $color = $priorityColors[$key];
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:.82rem">
                                <span class="fw-semibold" style="color:{{ $color }}">{{ $label }}</span>
                                <span class="text-muted">{{ $count }} jobs &middot; {{ $pct }}%</span>
                            </div>
                            <div class="progress" style="height:10px;border-radius:5px;background:#f1f3f5">
                                <div class="progress-bar"
                                     style="width:{{ $pct }}%;background:{{ $color }};border-radius:5px;transition:width .6s ease">
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- 4. Activity heatmap --}}
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="fw-semibold mb-3" style="font-size:.875rem">
                    <i class="bi bi-calendar3 me-2 text-warning"></i>Activity Heatmap
                    <span class="text-muted fw-normal" style="font-size:.72rem">— last 30 days</span>
                </div>
                @php
                    $maxU = max(array_filter($perf['units']) ?: [1]);
                @endphp
                <div class="d-flex flex-wrap gap-1 align-content-start">
                    @foreach($perf['labels'] as $i => $label)
                        @php
                            $u  = $perf['units'][$i];
                            $ot = $perf['overtime'][$i];
                            $intensity = $u > 0 ? number_format(max(0.2, $u / $maxU), 2) : 0;
                            if ($u === 0) {
                                $bg    = '#f1f3f5';
                                $title = $label . ': no activity';
                            } elseif ($ot) {
                                $bg    = 'rgba(255,193,7,' . $intensity . ')';
                                $title = $label . ': ' . number_format($u) . ' units (overtime)';
                            } else {
                                $bg    = 'rgba(' . $accentRgb . ',' . $intensity . ')';
                                $title = $label . ': ' . number_format($u) . ' units';
                            }
                        @endphp
                        <div title="{{ $title }}"
                             style="width:28px;height:28px;border-radius:4px;background:{{ $bg }};cursor:default"
                             data-bs-toggle="tooltip"
                             data-bs-title="{{ $title }}">
                        </div>
                    @endforeach
                </div>
                <div class="d-flex align-items-center gap-3 mt-3" style="font-size:.72rem;color:#adb5bd">
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:12px;height:12px;border-radius:2px;background:#f1f3f5;border:1px solid #dee2e6;display:inline-block"></span>
                        No activity
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:12px;height:12px;border-radius:2px;background:rgba({{ $accentRgb }},0.7);display:inline-block"></span>
                        Active
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:12px;height:12px;border-radius:2px;background:rgba(255,193,7,0.8);display:inline-block"></span>
                        Overtime
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    Chart.defaults.font.size   = 11;

    const accentRgb = '{{ $accentRgb }}';
    const labels    = {!! $labelsJson !!};
    const units     = {!! $unitsJson !!};
    const overtime  = {!! $overtimeJson !!};

    // ── 1. Throughput bar chart ────────────────────────────────
    const barColors = units.map((u, i) => {
        if (u === 0)      return 'rgba(200,200,200,0.25)';
        if (overtime[i])  return 'rgba(255,193,7,0.8)';
        return `rgba(${accentRgb},0.75)`;
    });

    const throughputCtx = document.getElementById('perf-throughput-chart');
    if (throughputCtx) {
        new Chart(throughputCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Units completed',
                    data: units,
                    backgroundColor: barColors,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const ot = overtime[ctx.dataIndex] ? ' ⚡ overtime' : '';
                                return ` ${ctx.parsed.y.toLocaleString()} units${ot}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 10, maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    // ── 2. Product mix donut ───────────────────────────────────
    const productCtx = document.getElementById('perf-product-chart');
    if (productCtx) {
        const productLabels = {!! $productLabelsJson !!};
        const productData   = {!! $productDataJson !!};

        const palette = [
            `rgba(${accentRgb},0.85)`,
            'rgba(25,135,84,0.8)',
            'rgba(255,193,7,0.8)',
            'rgba(220,53,69,0.8)',
            'rgba(111,66,193,0.8)',
            'rgba(13,202,240,0.8)',
        ];

        new Chart(productCtx, {
            type: 'doughnut',
            data: {
                labels: productLabels,
                datasets: [{
                    data: productData,
                    backgroundColor: palette.slice(0, productData.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()} units`
                        }
                    }
                }
            }
        });
    }

    // ── Bootstrap tooltips on heatmap cells ───────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
}());
</script>
@endpush
