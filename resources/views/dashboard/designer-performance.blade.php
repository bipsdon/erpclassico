@extends('layouts.app')

@section('title', 'Design Performance')
@section('page-title')
    <i class="bi bi-graph-up-arrow me-2"></i>Design Performance
@endsection

@push('styles')
<style>
:root { --ds-accent: #6366f1; --ds-card-r: .75rem; }
body.role-designer #main-content { background: #f0f0ff; }

.ds-tabs { border-bottom: none; gap: .5rem; }
.ds-tabs .nav-link {
    border: 1.5px solid #d1d5db; border-radius: 2rem !important;
    color: #4b5563; padding: .4rem 1.1rem;
    font-size: .82rem; font-weight: 600; background: #fff; transition: all .2s;
}
.ds-tabs .nav-link.active,
.ds-tabs .nav-link:hover { background: var(--ds-accent); border-color: var(--ds-accent); color: #fff; }

#ds-clock { font-size: .78rem; color: #6b7280; font-variant-numeric: tabular-nums; }

/* Lift all cards slightly */
body.role-designer .card { border-radius: var(--ds-card-r) !important; }
body.role-designer .card.border-0 { box-shadow: 0 2px 10px rgba(99,102,241,.08) !important; }

/* Section divider accent */
.ds-perf-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .5rem;
    padding-bottom: .75rem; margin-bottom: 1.25rem;
    border-bottom: 2px solid var(--ds-accent);
}
.ds-perf-header-title {
    font-size: .78rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #4b5563;
}
.ds-perf-header-title i { color: var(--ds-accent); }

/* Scope toggle pills */
.ds-scope-toggle .btn { border-radius: 2rem !important; font-size: .8rem; padding: .3rem .9rem; }

/* Filter card */
body.role-designer .card.border-0:has(#{{ '' }}-filter-form) { border-top: 3px solid var(--ds-accent) !important; }
</style>
@endpush

@section('content')

{{-- ── Pill tabs ──────────────────────────────────────────── --}}
<ul class="nav ds-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.designer') }}">
            <i class="bi bi-grid me-1"></i>My Queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard.designer.performance') }}">
            <i class="bi bi-bar-chart-line me-1"></i>Performance
        </a>
    </li>
    @if(auth()->user()->isDesigner())
    <li class="nav-item">
        <a class="nav-link" href="{{ route('schedule.gantt') }}">
            <i class="bi bi-bar-chart-steps me-1"></i>Deadline Map
        </a>
    </li>
    @endif
    <li class="nav-item ms-auto d-flex align-items-center">
        <span id="ds-clock"></span>
    </li>
</ul>

{{-- Performance charts (existing partial, unmodified) --}}
@include('partials.perf-charts', [
    'perf'        => $perf,
    'perfMine'    => $perfMine,
    'from'        => $from,
    'to'          => $to,
    'period'      => $period,
    'accentColor' => '#6366f1',
    'accentRgb'   => '99,102,241',
])

@endsection

@push('scripts')
<script>
(function clock() {
    const el = document.getElementById('ds-clock');
    if (!el) return;
    function tick() {
        const now  = new Date();
        const pad  = n => String(n).padStart(2,'0');
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        el.textContent = days[now.getDay()] + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }
    tick(); setInterval(tick, 1000);
})();
</script>
@endpush
