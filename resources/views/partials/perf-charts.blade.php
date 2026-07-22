{{--
    Performance visualisation partial.
    Required: $perf (department-wide), $perfMine (current user only)
    Required: $from (Carbon), $to (Carbon), $period (string)
    Optional: $accentColor (CSS color string), $accentRgb ('r,g,b')
    Optional: $filterRoute (named route for the filter form — defaults to current URL)
--}}
@php
    $accentColor ??= '#0d6efd';
    $accentRgb   ??= '13,110,253';
    $perfMine    ??= null;

    // Unique suffix so multiple partials on same page don't clash
    $uid = uniqid('pc');

    $productTypes = \App\Models\CapacityConfig::productTypes();

    $priorityColors = [
        'critical' => '#dc3545',
        'rush'     => '#ffc107',
        'normal'   => '#6c757d',
    ];

    // Pre-encode a dataset for JS consumption
    $encodePerf = function(array $p, array $pt): array {
        return [
            'labels'        => json_encode($p['labels']),
            'units'         => json_encode($p['units']),
            'overtime'      => json_encode($p['overtime']),
            'totalJobs'     => $p['totalJobs'],
            'totalUnits'    => $p['totalUnits'],
            'overtimeDays'  => $p['overtimeDays'],
            'activeDays'    => $p['activeDays'],
            'avgUnits'      => $p['activeDays'] > 0 ? round($p['totalUnits'] / $p['activeDays']) : 0,
            'byProduct'     => $p['byProduct'],
            'byPriority'    => $p['byPriority'],
            'productLabels' => json_encode(array_values(array_map(fn($k) => $pt[$k] ?? ucfirst($k), array_keys($p['byProduct'])))),
            'productData'   => json_encode(array_values($p['byProduct'])),
            'maxUnit'       => max(array_filter($p['units']) ?: [1]),
        ];
    };

    $deptData = $encodePerf($perf, $productTypes);
    $mineData = $perfMine ? $encodePerf($perfMine, $productTypes) : null;

    // Human-readable range label
    $rangeLabel = match($period) {
        'today' => 'Today',
        'week'  => 'This Week',
        'year'  => 'This Year',
        'custom'=> $from->format('d M Y') . ' – ' . $to->format('d M Y'),
        default => 'This Month',
    };
@endphp

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- FILTER BAR                                                 --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="" id="{{ $uid }}-filter-form" class="d-flex flex-wrap align-items-end gap-3">

            {{-- Preset buttons --}}
            <div>
                <div class="text-muted mb-1" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                    Quick Range
                </div>
                <div class="btn-group" role="group">
                    @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                        <a href="?period={{ $key }}"
                           class="btn btn-sm {{ $period === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Divider --}}
            <div class="vr d-none d-sm-block" style="height:38px"></div>

            {{-- Custom date range --}}
            <div class="d-flex align-items-end gap-2 flex-wrap">
                <div>
                    <label class="text-muted mb-1 d-block" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                        From
                    </label>
                    <input type="date" name="from" id="{{ $uid }}-from"
                           class="form-control form-control-sm"
                           style="min-width:140px"
                           value="{{ $period === 'custom' ? $from->format('Y-m-d') : '' }}"
                           max="{{ now()->format('Y-m-d') }}">
                </div>
                <div>
                    <label class="text-muted mb-1 d-block" style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">
                        To
                    </label>
                    <input type="date" name="to" id="{{ $uid }}-to"
                           class="form-control form-control-sm"
                           style="min-width:140px"
                           value="{{ $period === 'custom' ? $to->format('Y-m-d') : '' }}"
                           max="{{ now()->format('Y-m-d') }}">
                </div>
                <input type="hidden" name="period" id="{{ $uid }}-period-input" value="custom">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-funnel me-1"></i>Apply
                </button>
            </div>

            {{-- Active range badge --}}
            <div class="ms-auto">
                <span class="badge border text-secondary bg-light px-3 py-2" style="font-size:.78rem">
                    <i class="bi bi-calendar-range me-1"></i>
                    {{ $rangeLabel }}
                </span>
            </div>

        </form>
    </div>
</div>

{{-- ── Section header + dept/mine tab switcher ─────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2"
     style="border-bottom:2px solid #dee2e6">
    <span style="font-size:.875rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#495057">
        <i class="bi bi-graph-up-arrow me-2" style="color:{{ $accentColor }}"></i>
        Performance — {{ $rangeLabel }}
    </span>
    @if($perfMine !== null)
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-primary py-1 px-3" style="font-size:.8rem"
                    id="{{ $uid }}-dept-tab">
                <i class="bi bi-people me-1"></i>Department
            </button>
            <button class="btn btn-sm btn-outline-secondary py-1 px-3" style="font-size:.8rem"
                    id="{{ $uid }}-mine-tab">
                <i class="bi bi-person-fill me-1"></i>My Stats
            </button>
        </div>
    @endif
</div>

{{-- ── KPI strip ────────────────────────────────────────────── --}}
<div class="row g-3 mb-4" id="{{ $uid }}-kpis">
    @foreach([
        ['key' => 'totalJobs',    'label' => 'Orders Completed',      'color' => $accentColor],
        ['key' => 'totalUnits',   'label' => 'Units Processed',        'color' => $accentColor],
        ['key' => 'avgUnits',     'label' => 'Avg Units / Active Day', 'color' => '#198754'],
        ['key' => 'overtimeDays', 'label' => 'Overtime Days',          'color' => null],
    ] as $kpi)
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm h-100 text-center py-3">
                <div class="{{ $uid }}-kpi-val"
                     data-key="{{ $kpi['key'] }}"
                     data-dept="{{ $deptData[$kpi['key']] }}"
                     data-mine="{{ $mineData ? $mineData[$kpi['key']] : $deptData[$kpi['key']] }}"
                     style="font-size:1.75rem;font-weight:700;color:{{ $kpi['color'] ?? ($deptData['overtimeDays'] > 0 ? '#ffc107' : '#198754') }}">
                    {{ number_format($deptData[$kpi['key']]) }}
                </div>
                <div class="text-muted" style="font-size:.75rem">{{ $kpi['label'] }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- ── Charts row ───────────────────────────────────────────── --}}
<div class="row g-4 mb-4">

    {{-- Throughput bar chart --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-semibold" style="font-size:.875rem">
                        <i class="bi bi-bar-chart-fill me-2" style="color:{{ $accentColor }}"></i>Daily Throughput
                    </span>
                    <span class="text-muted" style="font-size:.75rem">units completed per day</span>
                </div>
                <canvas id="{{ $uid }}-throughput" height="110"></canvas>
            </div>
        </div>
    </div>

    {{-- Product mix donut --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="fw-semibold mb-3" style="font-size:.875rem">
                    <i class="bi bi-pie-chart-fill me-2" style="color:{{ $accentColor }}"></i>Product Mix
                </div>
                <div id="{{ $uid }}-product-wrap" class="flex-grow-1 d-flex flex-column">
                    @if(empty($deptData['byProduct']))
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted" style="font-size:.85rem">
                            <div class="text-center">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>No completed jobs yet
                            </div>
                        </div>
                    @else
                        <canvas id="{{ $uid }}-product" style="max-height:180px"></canvas>
                        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center" id="{{ $uid }}-product-labels">
                            @foreach($deptData['byProduct'] as $type => $units)
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

</div>

{{-- ── Second row ───────────────────────────────────────────── --}}
<div class="row g-4 mb-4">

    {{-- Priority breakdown --}}
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="fw-semibold mb-3" style="font-size:.875rem">
                    <i class="bi bi-flag-fill me-2 text-danger"></i>Completed Jobs by Priority
                </div>
                <div id="{{ $uid }}-priority-bars">
                    @if(empty($deptData['byPriority']))
                        <div class="text-center text-muted py-4" style="font-size:.85rem">
                            <i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No completed jobs yet
                        </div>
                    @else
                        @php $totalPri = array_sum($deptData['byPriority']); @endphp
                        @foreach(['critical' => 'Critical', 'rush' => 'Rush', 'normal' => 'Normal'] as $key => $label)
                            @php
                                $count = $deptData['byPriority'][$key] ?? 0;
                                $pct   = $totalPri > 0 ? round($count / $totalPri * 100) : 0;
                                $color = $priorityColors[$key];
                            @endphp
                            <div class="mb-3" data-pri="{{ $key }}">
                                <div class="d-flex justify-content-between mb-1" style="font-size:.82rem">
                                    <span class="fw-semibold" style="color:{{ $color }}">{{ $label }}</span>
                                    <span class="text-muted pri-label">{{ $count }} jobs &middot; {{ $pct }}%</span>
                                </div>
                                <div class="progress" style="height:10px;border-radius:5px;background:#f1f3f5">
                                    <div class="progress-bar pri-bar"
                                         style="width:{{ $pct }}%;background:{{ $color }};border-radius:5px;transition:width .5s ease">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Activity heatmap --}}
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="fw-semibold mb-3" style="font-size:.875rem">
                    <i class="bi bi-calendar3 me-2 text-warning"></i>Activity Heatmap
                    <span class="text-muted fw-normal" style="font-size:.72rem">— {{ $rangeLabel }}</span>
                </div>
                <div id="{{ $uid }}-heatmap" class="d-flex flex-wrap gap-1 align-content-start">
                    @php $maxU = max(array_filter($perf['units']) ?: [1]); @endphp
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
                             data-bs-toggle="tooltip"
                             data-bs-title="{{ $title }}"
                             data-idx="{{ $i }}"
                             class="{{ $uid }}-cell"
                             style="width:28px;height:28px;border-radius:4px;background:{{ $bg }};cursor:default;transition:background .3s">
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

    const uid         = '{{ $uid }}';
    const accentRgb   = '{{ $accentRgb }}';
    const accentColor = '{{ $accentColor }}';

    const DATA = {
        dept: {
            labels:        {!! $deptData['labels'] !!},
            units:         {!! $deptData['units'] !!},
            overtime:      {!! $deptData['overtime'] !!},
            totalJobs:     {{ $deptData['totalJobs'] }},
            totalUnits:    {{ $deptData['totalUnits'] }},
            avgUnits:      {{ $deptData['avgUnits'] }},
            overtimeDays:  {{ $deptData['overtimeDays'] }},
            byProduct:     {!! $deptData['productData'] !!},
            productLabels: {!! $deptData['productLabels'] !!},
            byPriority: {
                critical: {{ $deptData['byPriority']['critical'] ?? 0 }},
                rush:     {{ $deptData['byPriority']['rush'] ?? 0 }},
                normal:   {{ $deptData['byPriority']['normal'] ?? 0 }},
            },
        },
        @if($mineData)
        mine: {
            labels:        {!! $mineData['labels'] !!},
            units:         {!! $mineData['units'] !!},
            overtime:      {!! $mineData['overtime'] !!},
            totalJobs:     {{ $mineData['totalJobs'] }},
            totalUnits:    {{ $mineData['totalUnits'] }},
            avgUnits:      {{ $mineData['avgUnits'] }},
            overtimeDays:  {{ $mineData['overtimeDays'] }},
            byProduct:     {!! $mineData['productData'] !!},
            productLabels: {!! $mineData['productLabels'] !!},
            byPriority: {
                critical: {{ $mineData['byPriority']['critical'] ?? 0 }},
                rush:     {{ $mineData['byPriority']['rush'] ?? 0 }},
                normal:   {{ $mineData['byPriority']['normal'] ?? 0 }},
            },
        },
        @endif
    };

    const palette = [
        `rgba(${accentRgb},0.85)`,
        'rgba(25,135,84,0.8)',
        'rgba(255,193,7,0.8)',
        'rgba(220,53,69,0.8)',
        'rgba(111,66,193,0.8)',
        'rgba(13,202,240,0.8)',
    ];

    // ── Build charts ──────────────────────────────────────────
    let throughputChart = null;
    let productChart    = null;

    function barColors(units, overtime) {
        return units.map((u, i) => {
            if (u === 0)     return 'rgba(200,200,200,0.25)';
            if (overtime[i]) return 'rgba(255,193,7,0.8)';
            return `rgba(${accentRgb},0.75)`;
        });
    }

    function initCharts(scope) {
        const d = DATA[scope] || DATA.dept;

        // Throughput
        const tCtx = document.getElementById(uid + '-throughput');
        if (tCtx) {
            if (throughputChart) {
                throughputChart.data.labels                      = d.labels;
                throughputChart.data.datasets[0].data            = d.units;
                throughputChart.data.datasets[0].backgroundColor = barColors(d.units, d.overtime);
                throughputChart._overtimeRef = d.overtime;
                throughputChart.update();
            } else {
                throughputChart = new Chart(tCtx, {
                    type: 'bar',
                    data: {
                        labels: d.labels,
                        datasets: [{
                            label: 'Units completed',
                            data: d.units,
                            backgroundColor: barColors(d.units, d.overtime),
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
                                        const ot = d.overtime[ctx.dataIndex] ? ' ⚡ overtime' : '';
                                        return ` ${ctx.parsed.y.toLocaleString()} units${ot}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 45 } },
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { precision: 0 } }
                        }
                    }
                });
                throughputChart._overtimeRef = d.overtime;
            }
        }

        // Product donut
        const pCtx = document.getElementById(uid + '-product');
        if (pCtx) {
            if (productChart) {
                productChart.data.labels           = d.productLabels;
                productChart.data.datasets[0].data = d.byProduct;
                productChart.update();
            } else {
                productChart = new Chart(pCtx, {
                    type: 'doughnut',
                    data: {
                        labels: d.productLabels,
                        datasets: [{
                            data: d.byProduct,
                            backgroundColor: palette.slice(0, d.byProduct.length),
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '65%',
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()} units`
                                }
                            }
                        }
                    }
                });
            }
        }
    }

    // ── Update KPIs ───────────────────────────────────────────
    function updateKpis(scope) {
        document.querySelectorAll('.' + uid + '-kpi-val').forEach(el => {
            const val = el.dataset[scope] ?? el.dataset.dept;
            el.textContent = parseInt(val).toLocaleString();
            if (el.dataset.key === 'overtimeDays') {
                el.style.color = parseInt(val) > 0 ? '#ffc107' : '#198754';
            }
        });
    }

    // ── Update priority bars ──────────────────────────────────
    function updatePriority(scope) {
        const d     = DATA[scope] || DATA.dept;
        const total = d.byPriority.critical + d.byPriority.rush + d.byPriority.normal;
        ['critical', 'rush', 'normal'].forEach(key => {
            const wrap = document.querySelector(`[data-pri="${key}"]`);
            if (!wrap) return;
            const count = d.byPriority[key] || 0;
            const pct   = total > 0 ? Math.round(count / total * 100) : 0;
            wrap.querySelector('.pri-label').textContent = `${count} jobs · ${pct}%`;
            wrap.querySelector('.pri-bar').style.width   = pct + '%';
        });
    }

    // ── Update heatmap ────────────────────────────────────────
    function updateHeatmap(scope) {
        const d    = DATA[scope] || DATA.dept;
        const maxU = Math.max(...d.units.filter(v => v > 0), 1);
        document.querySelectorAll('.' + uid + '-cell').forEach(cell => {
            const i         = parseInt(cell.dataset.idx);
            const u         = d.units[i] || 0;
            const ot        = d.overtime[i];
            const intensity = u > 0 ? Math.max(0.2, u / maxU).toFixed(2) : 0;
            let bg, title;
            if (u === 0) {
                bg    = '#f1f3f5';
                title = d.labels[i] + ': no activity';
            } else if (ot) {
                bg    = `rgba(255,193,7,${intensity})`;
                title = `${d.labels[i]}: ${u.toLocaleString()} units (overtime)`;
            } else {
                bg    = `rgba(${accentRgb},${intensity})`;
                title = `${d.labels[i]}: ${u.toLocaleString()} units`;
            }
            cell.style.background = bg;
            const tip = bootstrap.Tooltip.getInstance(cell);
            if (tip) tip.setContent({ '.tooltip-inner': title });
            cell.setAttribute('title', title);
        });
    }

    // ── Dept / Mine tab switching ─────────────────────────────
    let currentScope = 'dept';

    function switchScope(scope) {
        currentScope = scope;
        initCharts(scope);
        updateKpis(scope);
        updatePriority(scope);
        updateHeatmap(scope);

        const deptBtn = document.getElementById(uid + '-dept-tab');
        const mineBtn = document.getElementById(uid + '-mine-tab');
        if (deptBtn && mineBtn) {
            if (scope === 'dept') {
                deptBtn.className = 'btn btn-sm btn-primary py-1 px-3';
                mineBtn.className = 'btn btn-sm btn-outline-secondary py-1 px-3';
            } else {
                deptBtn.className = 'btn btn-sm btn-outline-secondary py-1 px-3';
                mineBtn.className = 'btn btn-sm btn-primary py-1 px-3';
            }
        }
    }

    document.getElementById(uid + '-dept-tab')?.addEventListener('click', () => switchScope('dept'));
    document.getElementById(uid + '-mine-tab')?.addEventListener('click', () => switchScope('mine'));

    // ── Custom date range: auto-set period=custom on date input ──
    const fromInput   = document.getElementById(uid + '-from');
    const toInput     = document.getElementById(uid + '-to');
    const periodInput = document.getElementById(uid + '-period-input');

    // Enforce from ≤ to
    fromInput?.addEventListener('change', () => {
        if (toInput && fromInput.value && toInput.value && fromInput.value > toInput.value) {
            toInput.value = fromInput.value;
        }
    });
    toInput?.addEventListener('change', () => {
        if (fromInput && toInput.value && fromInput.value && toInput.value < fromInput.value) {
            fromInput.value = toInput.value;
        }
    });

    // ── Init ──────────────────────────────────────────────────
    initCharts('dept');

    document.querySelectorAll('.' + uid + '-cell').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

}());
</script>
@endpush
