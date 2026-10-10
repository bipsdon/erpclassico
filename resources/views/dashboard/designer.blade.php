@extends('layouts.app')

@section('title', 'Designer Dashboard')
@section('page-title')
    <i class="bi bi-pencil-square me-2"></i>Design Studio
@endsection

@push('styles')
<style>
/* ── Designer dashboard theme ───────────────────────────── */
:root {
    --ds-accent:   #6366f1;   /* indigo */
    --ds-surface:  #f8f8ff;
    --ds-card-r:   .75rem;
}

body.role-designer #main-content { background: #f0f0ff; }

/* ── Tab nav ─────────────────────────────────────────────── */
.ds-tabs { border-bottom: none; gap: .5rem; }
.ds-tabs .nav-link {
    border: 1.5px solid #d1d5db;
    border-radius: 2rem !important;
    color: #4b5563;
    padding: .4rem 1.1rem;
    font-size: .82rem;
    font-weight: 600;
    background: #fff;
    transition: all .2s;
}
.ds-tabs .nav-link.active,
.ds-tabs .nav-link:hover {
    background: var(--ds-accent);
    border-color: var(--ds-accent);
    color: #fff;
}

/* ── Stat pills ──────────────────────────────────────────── */
.ds-stat {
    background: #fff;
    border-radius: 1rem;
    padding: .9rem 1.1rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    transition: transform .15s, box-shadow .15s;
    cursor: default;
    border: 1.5px solid transparent;
}
.ds-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.11); }
.ds-stat.warn  { border-color: #f59e0b; background: #fffbeb; }
.ds-stat-num   { font-size: 2rem; font-weight: 800; line-height: 1; }
.ds-stat-lbl   { font-size: .72rem; color: #6b7280; margin-top: .15rem; }
.ds-stat-icon  {
    width: 40px; height: 40px; border-radius: .6rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}

/* ── Progress ring ───────────────────────────────────────── */
.ds-ring-wrap { position: relative; display: inline-flex; align-items: center; justify-content: center; }
.ds-ring-wrap svg { transform: rotate(-90deg); }
.ds-ring-track { fill: none; stroke: #e5e7eb; }
.ds-ring-fill  { fill: none; stroke: var(--ds-accent); stroke-linecap: round; transition: stroke-dashoffset 1.2s ease; }
.ds-ring-label {
    position: absolute;
    text-align: center;
    font-weight: 800;
    font-size: 1.05rem;
    color: var(--ds-accent);
    line-height: 1;
}
.ds-ring-sub { font-size: .6rem; color: #9ca3af; display: block; margin-top: .1rem; }

/* ── Live clock ──────────────────────────────────────────── */
#ds-clock { font-size: .78rem; color: #6b7280; font-variant-numeric: tabular-nums; }

/* ── Hero "Do This Next" ─────────────────────────────────── */
.ds-hero {
    border-radius: var(--ds-card-r);
    box-shadow: 0 4px 24px rgba(99,102,241,.18);
    border: none;
    overflow: hidden;
    position: relative;
}
.ds-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.08) 0%, transparent 60%);
    pointer-events: none;
}
/* Urgency pulse ring around the icon */
@keyframes ds-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(255,255,255,.45); }
    70%  { box-shadow: 0 0 0 12px rgba(255,255,255,0); }
    100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
}
.ds-hero-icon {
    width: 56px; height: 56px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; flex-shrink: 0;
    background: rgba(255,255,255,.2);
}
.ds-hero-icon.pulse { animation: ds-pulse 1.8s infinite; }
.ds-hero-ref   { font-size: 1.3rem; font-weight: 800; letter-spacing: .3px; }
.ds-hero-meta  { font-size: .85rem; opacity: .85; }
.ds-hero-btn {
    background: rgba(255,255,255,.2);
    border: 1.5px solid rgba(255,255,255,.45);
    color: #fff;
    border-radius: .5rem;
    padding: .45rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: background .15s;
}
.ds-hero-btn:hover { background: rgba(255,255,255,.32); color: #fff; }
.ds-hero-divider { border-color: rgba(255,255,255,.2); }
.ds-brief-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .65rem;
    border-radius: 2rem;
    font-size: .75rem; font-weight: 600;
    border: 1px solid rgba(255,255,255,.3);
    background: rgba(255,255,255,.15);
    color: #fff;
}
.ds-brief-chip.missing {
    background: #fff;
    color: #dc3545;
    border-color: #dc3545;
}

/* ── No-brief warning panel ──────────────────────────────── */
.ds-nobrief-panel {
    border-radius: var(--ds-card-r);
    border-left: 4px solid #f59e0b;
    background: #fffbeb;
    overflow: hidden;
}
.ds-nobrief-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .6rem 1rem;
    border-bottom: 1px solid #fef3c7;
    transition: background .15s;
}
.ds-nobrief-item:last-child { border-bottom: none; }
.ds-nobrief-item:hover { background: #fef9e7; }

/* ── Order cards ─────────────────────────────────────────── */
.ds-order-card {
    border-radius: var(--ds-card-r);
    background: #fff;
    border: none;
    box-shadow: 0 1px 5px rgba(0,0,0,.07);
    margin-bottom: .75rem;
    overflow: hidden;
    transition: box-shadow .18s, transform .18s;
    border-left: 4px solid #e5e7eb;
    cursor: pointer;
}
.ds-order-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.11); transform: translateY(-1px); }
.ds-order-card.health-green  { border-left-color: #22c55e; }
.ds-order-card.health-yellow { border-left-color: #f59e0b; }
.ds-order-card.health-red    { border-left-color: #ef4444; }
.ds-order-card.pinned        { border-left-color: #6366f1; }
.ds-order-card.no-brief      { background: #fffdf5; }

.ds-card-head  { padding: .75rem 1rem .6rem; }
.ds-card-body  {
    padding: .6rem 1rem .75rem;
    border-top: 1px solid #f3f4f6;
    display: none;   /* collapsed by default */
}
.ds-card-body.open { display: block; }

.ds-card-ref   { font-size: .95rem; font-weight: 700; }
.ds-card-meta  { font-size: .78rem; color: #6b7280; margin-top: .1rem; }
.ds-chip {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .18rem .55rem;
    border-radius: 2rem;
    font-size: .7rem; font-weight: 600;
    white-space: nowrap;
}
.ds-chip-red    { background: #fee2e2; color: #dc2626; }
.ds-chip-yellow { background: #fef3c7; color: #b45309; }
.ds-chip-green  { background: #dcfce7; color: #16a34a; }
.ds-chip-blue   { background: #e0e7ff; color: #4338ca; }
.ds-chip-gray   { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.ds-chip-amber  { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

.ds-card-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
}
.ds-card-avatar-initials {
    width: 34px; height: 34px; border-radius: 50%;
    background: #e0e7ff; color: #4338ca;
    font-size: .72rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* Expand chevron */
.ds-chevron {
    transition: transform .2s;
    color: #9ca3af;
    font-size: .8rem;
}
.ds-order-card.expanded .ds-chevron { transform: rotate(180deg); }

/* Action row in card body */
.ds-card-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .75rem; }
.ds-btn-start {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .4rem .9rem; border-radius: .45rem;
    font-size: .8rem; font-weight: 600;
    background: #e0e7ff; color: #4338ca; border: none;
    cursor: pointer; transition: background .15s;
}
.ds-btn-start:hover { background: #c7d2fe; }
.ds-btn-done {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .4rem .9rem; border-radius: .45rem;
    font-size: .8rem; font-weight: 600;
    background: #dcfce7; color: #16a34a; border: none;
    cursor: pointer; transition: background .15s;
}
.ds-btn-done:hover { background: #bbf7d0; }
.ds-btn-view {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .4rem .9rem; border-radius: .45rem;
    font-size: .8rem; font-weight: 600;
    background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;
    text-decoration: none; transition: background .15s;
}
.ds-btn-view:hover { background: #e5e7eb; color: #111827; }

/* Spec preview snippet */
.ds-spec-preview {
    font-size: .78rem; color: #374151;
    background: #f9fafb; border-radius: .4rem;
    padding: .5rem .75rem; margin-top: .5rem;
    line-height: 1.55;
    border-left: 3px solid #6366f1;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

/* ── Section title (refined) ─────────────────────────────── */
.ds-section {
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    color: #9ca3af;
    margin-bottom: .75rem;
    display: flex; align-items: center; gap: .4rem;
}
.ds-section::after {
    content: ''; flex: 1; height: 1px; background: #e5e7eb;
}

/* ── Sidebar upcoming cards ──────────────────────────────── */
.ds-upcoming-item {
    display: flex; align-items: center; gap: .6rem;
    padding: .55rem .75rem;
    border-radius: .5rem;
    margin-bottom: .35rem;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    border-left: 3px solid #e5e7eb;
    font-size: .8rem;
    transition: transform .12s;
}
.ds-upcoming-item:hover { transform: translateX(2px); }
.ds-upcoming-item.urgent { border-left-color: #ef4444; }
.ds-upcoming-item.high   { border-left-color: #f59e0b; }

/* ── Empty states ────────────────────────────────────────── */
.ds-empty {
    text-align: center; padding: 2.5rem 1rem;
    color: #9ca3af;
}
.ds-empty i { font-size: 2.5rem; display: block; margin-bottom: .6rem; color: #d1d5db; }
</style>
@endpush

@section('content')

@php
    $noBriefOrders = array_filter($queue->orders, fn($o) => $o->isMissingBrief());
    $noBriefCount  = count($noBriefOrders);
    $health        = $queue->healthSummary();
    $total         = count($queue->orders);
    $donePct       = $total > 0
        ? round(collect($queue->orders)->filter(fn($o) => $o->orderStatus === 'in_progress')->count() / $total * 100)
        : 0;
    // circumference for r=36 ring
    $circ   = 2 * M_PI * 36;
    $offset = $circ - ($donePct / 100 * $circ);
@endphp

{{-- ── Tab navigation ──────────────────────────────────────── --}}
<ul class="nav ds-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard.designer') }}">
            <i class="bi bi-grid me-1"></i>My Queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.designer.performance') }}">
            <i class="bi bi-bar-chart-line me-1"></i>Performance
        </a>
    </li>
    <li class="nav-item ms-auto d-flex align-items-center">
        <span id="ds-clock"></span>
    </li>
</ul>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- STAT PILLS                                                 --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4 align-items-stretch">

    {{-- Progress ring --}}
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="ds-stat h-100 d-flex align-items-center gap-3">
            <div class="ds-ring-wrap">
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle class="ds-ring-track" cx="40" cy="40" r="36" stroke-width="7"/>
                    <circle class="ds-ring-fill"  cx="40" cy="40" r="36" stroke-width="7"
                            stroke-dasharray="{{ round($circ, 2) }}"
                            stroke-dashoffset="{{ round($offset, 2) }}"
                            id="ds-ring-fill"/>
                </svg>
                <div class="ds-ring-label">
                    <span id="ds-ring-pct">{{ $donePct }}%</span>
                    <span class="ds-ring-sub">active</span>
                </div>
            </div>
            <div>
                <div class="ds-stat-num" style="color:var(--ds-accent)">{{ $total }}</div>
                <div class="ds-stat-lbl">Today's orders</div>
            </div>
        </div>
    </div>

    {{-- Critical --}}
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="ds-stat h-100 d-flex align-items-center gap-3">
            <div class="ds-stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
            <div>
                <div class="ds-stat-num text-danger ds-counter" data-target="{{ $health['red'] }}">0</div>
                <div class="ds-stat-lbl">Critical</div>
            </div>
        </div>
    </div>

    {{-- At Risk --}}
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="ds-stat h-100 d-flex align-items-center gap-3">
            <div class="ds-stat-icon bg-warning bg-opacity-10" style="color:#b45309">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="ds-stat-num ds-counter" data-target="{{ $health['yellow'] }}" style="color:#b45309">0</div>
                <div class="ds-stat-lbl">At Risk</div>
            </div>
        </div>
    </div>

    {{-- On Track --}}
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="ds-stat h-100 d-flex align-items-center gap-3">
            <div class="ds-stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="ds-stat-num text-success ds-counter" data-target="{{ $health['green'] }}">0</div>
                <div class="ds-stat-lbl">On Track</div>
            </div>
        </div>
    </div>

    {{-- No Brief --}}
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="ds-stat h-100 d-flex align-items-center gap-3 {{ $noBriefCount > 0 ? 'warn' : '' }}">
            <div class="ds-stat-icon {{ $noBriefCount > 0 ? '' : 'bg-light text-muted' }}"
                 style="{{ $noBriefCount > 0 ? 'background:#fef3c7;color:#b45309' : '' }}">
                <i class="bi bi-file-earmark-x"></i>
            </div>
            <div>
                <div class="ds-stat-num ds-counter {{ $noBriefCount > 0 ? '' : 'text-muted' }}"
                     data-target="{{ $noBriefCount }}"
                     style="{{ $noBriefCount > 0 ? 'color:#b45309' : '' }}">0</div>
                <div class="ds-stat-lbl">No Brief</div>
            </div>
        </div>
    </div>

    {{-- History link --}}
    <div class="col-6 col-sm-4 col-lg-2">
        <a href="{{ route('history.index', ['department' => 'design']) }}"
           class="ds-stat h-100 d-flex align-items-center gap-3 text-decoration-none"
           style="color:inherit">
            <div class="ds-stat-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="ds-stat-num text-secondary" style="font-size:1.1rem;margin-top:.2rem">
                    <i class="bi bi-arrow-right"></i>
                </div>
                <div class="ds-stat-lbl">History</div>
            </div>
        </a>
    </div>

</div>

{{-- ── No-brief warning panel ───────────────────────────────── --}}
@if($noBriefCount > 0)
<div class="ds-nobrief-panel shadow-sm mb-4">
    <div class="px-3 py-2 d-flex align-items-center justify-content-between"
         style="background:#fef3c7;border-bottom:1px solid #fde68a">
        <span class="fw-semibold" style="font-size:.82rem;color:#92400e">
            <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
            {{ $noBriefCount }} order{{ $noBriefCount !== 1 ? 's have' : ' has' }} no brief — contact Pipeline Manager before starting
        </span>
        <span class="ds-chip ds-chip-amber">Ask PM</span>
    </div>
    @foreach($noBriefOrders as $order)
        <div class="ds-nobrief-item">
            <span class="health-dot bg-{{ $order->healthBadge() }}"></span>
            <div class="flex-grow-1">
                <span class="fw-semibold" style="font-size:.85rem">
                    @if($order->whatsappOrderId)
                        <i class="bi bi-whatsapp text-success me-1"></i>{{ $order->whatsappOrderId }}
                    @else
                        {{ $order->orderNumber }}
                    @endif
                </span>
                <span class="text-muted ms-2" style="font-size:.75rem">
                    {{ $order->customerName }} · {{ number_format($order->quantity) }} {{ $order->productTypeLabel }}
                </span>
            </div>
            <span class="ds-chip {{ $order->daysUntilDelivery < 0 ? 'ds-chip-red' : ($order->daysUntilDelivery <= 1 ? 'ds-chip-yellow' : 'ds-chip-gray') }}">
                {{ $order->urgencyLabel() }}
            </span>
            <a href="{{ route('orders.show', $order->orderId) }}"
               class="ds-chip ds-chip-gray text-decoration-none ms-1">
                <i class="bi bi-eye"></i> View
            </a>
        </div>
    @endforeach
</div>
@endif

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- MAIN LAYOUT                                                --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="row g-4">

    {{-- ── Left: Hero + Order cards ──────────────────────── --}}
    <div class="col-12 col-xl-8">

        {{-- ── Do This Next hero card ──────────────────── --}}
        @php
            $next = collect($queue->orders)->first(
                fn($o) => !in_array($o->orderStatus, ['in_progress', 'completed'])
            );
        @endphp

        @if($next)
        @php
            $isUrgent     = $next->daysUntilDelivery <= 0;
            $isAtRisk     = $next->daysUntilDelivery === 1;
            $briefMissing = $next->isMissingBrief();

            if ($isUrgent) {
                $heroBg   = 'linear-gradient(135deg,#dc2626,#b91c1c)';
                $heroIcon = 'bi-exclamation-triangle-fill';
                $heroLbl  = $next->isLate ? 'LATE — Start immediately' : 'Due TODAY — Work on this first';
            } elseif ($isAtRisk || $next->healthStatus === 'yellow') {
                $heroBg   = 'linear-gradient(135deg,#d97706,#b45309)';
                $heroIcon = 'bi-clock-fill';
                $heroLbl  = 'Due tomorrow — Your next priority';
            } else {
                $heroBg   = 'linear-gradient(135deg,#6366f1,#4f46e5)';
                $heroIcon = 'bi-arrow-right-circle-fill';
                $heroLbl  = 'Work on this next';
            }
        @endphp

        <div class="ds-hero mb-4" style="background:{{ $heroBg }}">
            <div class="p-4">

                {{-- Top row --}}
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="ds-hero-icon {{ $isUrgent ? 'pulse' : '' }}">
                        <i class="bi {{ $heroIcon }} text-white fs-3"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-white fw-semibold" style="font-size:.78rem;opacity:.8;text-transform:uppercase;letter-spacing:.8px">{{ $heroLbl }}</div>
                        <div class="ds-hero-ref text-white mt-1">
                            @if($next->whatsappOrderId)
                                <i class="bi bi-whatsapp me-1" style="opacity:.8"></i>{{ $next->whatsappOrderId }}
                            @else
                                {{ $next->orderNumber }}
                            @endif
                            @if($next->isPinned())
                                <span style="font-size:.7rem;opacity:.75;font-weight:500">
                                    <i class="bi bi-pin-angle-fill ms-2 me-1"></i>Pinned
                                </span>
                            @endif
                        </div>
                        <div class="ds-hero-meta text-white mt-1">
                            {{ $next->customerName }}
                            <span style="opacity:.5;margin:0 .35rem">·</span>
                            {{ number_format($next->quantity) }} {{ $next->productTypeLabel }}
                            <span style="opacity:.5;margin:0 .35rem">·</span>
                            <strong>{{ $next->urgencyLabel() }}</strong>
                        </div>
                    </div>
                    <a href="{{ route('orders.show', $next->orderId) }}" class="ds-hero-btn">
                        <i class="bi bi-folder2-open me-1"></i>Open Brief
                    </a>
                </div>

                {{-- Brief status row --}}
                <hr class="ds-hero-divider my-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($briefMissing)
                        <span class="ds-brief-chip missing">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            No brief or spec — ask PM before starting
                        </span>
                    @else
                        @if($next->attachmentsCount > 0)
                            <span class="ds-brief-chip">
                                <i class="bi bi-paperclip"></i>
                                {{ $next->attachmentsCount }} file{{ $next->attachmentsCount !== 1 ? 's' : '' }} attached
                            </span>
                        @else
                            <span class="ds-brief-chip" style="opacity:.6">
                                <i class="bi bi-paperclip"></i> No files
                            </span>
                        @endif
                        @if($next->hasDetails)
                            <span class="ds-brief-chip">
                                <i class="bi bi-file-text"></i> Spec written
                            </span>
                        @else
                            <span class="ds-brief-chip" style="opacity:.6">
                                <i class="bi bi-file-earmark"></i> No spec
                            </span>
                        @endif
                        @if($next->hasNotes)
                            <span class="ds-brief-chip" style="background:#fef3c7;color:#92400e;border-color:#fde68a">
                                <i class="bi bi-sticky-fill"></i> PM left notes
                            </span>
                        @endif
                    @endif

                    {{-- Priority badge --}}
                    <span class="ms-auto ds-chip
                        {{ $next->priority === 'critical' ? 'ds-chip-red' : ($next->priority === 'rush' ? 'ds-chip-yellow' : 'ds-chip-gray') }}">
                        {{ ucfirst($next->priority) }}
                    </span>
                </div>

                {{-- Action buttons --}}
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    @if(in_array($next->orderStatus, ['pending', 'on_hold']))
                        <form method="POST"
                              action="{{ route('production.start', ['department' => $next->department, 'orderId' => $next->orderId]) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="ds-hero-btn" style="font-size:.85rem;padding:.5rem 1.1rem">
                                <i class="bi bi-play-fill me-1"></i>Start Working
                            </button>
                        </form>
                    @endif
                    <form method="POST"
                          action="{{ route('production.complete', ['department' => $next->department, 'orderId' => $next->orderId]) }}"
                          onsubmit="return confirm('Mark {{ addslashes($next->whatsappOrderId ?? $next->orderNumber) }} as complete in Design?')">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="ds-hero-btn"
                                style="background:rgba(255,255,255,.3);font-size:.85rem;padding:.5rem 1.1rem">
                            <i class="bi bi-check2-all me-1"></i>Mark Done
                        </button>
                    </form>
                </div>

            </div>
        </div>
        @endif

        {{-- ── Order cards list ─────────────────────── --}}
        <div class="ds-section"><i class="bi bi-list-task"></i> Today's Design Queue <span style="font-weight:400;color:#9ca3af;text-transform:none;letter-spacing:0">— {{ now()->format('l, d F Y') }}</span></div>

        @if(count($queue->orders) === 0)
            <div class="ds-empty">
                <i class="bi bi-inbox"></i>
                No orders scheduled for today — enjoy the quiet.
            </div>
        @else
            <div id="ds-cards">
            @foreach($queue->orders as $i => $order)
            @php
                $healthClass = match($order->healthStatus) {
                    'red'    => 'health-red',
                    'yellow' => 'health-yellow',
                    default  => 'health-green',
                };
                $extraClass  = $order->isPinned()       ? 'pinned'
                             : ($order->isMissingBrief() ? 'no-brief' : '');
                $chipUrgency = $order->daysUntilDelivery < 0  ? 'ds-chip-red'
                             : ($order->daysUntilDelivery <= 1 ? 'ds-chip-yellow'
                             :                                   'ds-chip-green');
                // Strip HTML tags from details for the snippet preview
                $specSnippet = $order->hasDetails ? '' : '';
            @endphp

            <div class="ds-order-card {{ $healthClass }} {{ $extraClass }}"
                 data-order="{{ $order->orderId }}"
                 onclick="dsToggleCard(this)">

                {{-- Card head: always visible --}}
                <div class="ds-card-head d-flex align-items-center gap-3">

                    {{-- Position --}}
                    <div class="flex-shrink-0 text-center" style="min-width:1.5rem">
                        @if($order->isPinned())
                            <i class="bi bi-pin-angle-fill text-indigo" style="color:var(--ds-accent)"></i>
                        @else
                            <span class="text-muted" style="font-size:.75rem">{{ $i + 1 }}</span>
                        @endif
                    </div>

                    {{-- Avatar --}}
                    @if($order->profilePictureUrl)
                        <img src="{{ $order->profilePictureUrl }}" alt="" class="ds-card-avatar">
                    @else
                        <div class="ds-card-avatar-initials">{{ $order->avatarInitials }}</div>
                    @endif

                    {{-- Ref + customer --}}
                    <div class="flex-grow-1 min-width-0">
                        <div class="ds-card-ref text-truncate">
                            @if($order->whatsappOrderId)
                                <i class="bi bi-whatsapp text-success me-1" style="font-size:.85rem"></i>{{ $order->whatsappOrderId }}
                            @else
                                <span class="text-muted" style="font-weight:500">{{ $order->orderNumber }}</span>
                            @endif
                        </div>
                        <div class="ds-card-meta text-truncate">
                            {{ $order->customerName }}
                            <span class="mx-1" style="opacity:.4">·</span>
                            {{ number_format($order->quantity) }} {{ $order->productTypeLabel }}
                        </div>
                    </div>

                    {{-- Right chips --}}
                    <div class="d-flex align-items-center gap-1 flex-shrink-0 flex-wrap justify-content-end">
                        <span class="ds-chip {{ $chipUrgency }}">{{ $order->urgencyLabel() }}</span>
                        <span class="ds-chip {{ $order->priority === 'critical' ? 'ds-chip-red' : ($order->priority === 'rush' ? 'ds-chip-yellow' : 'ds-chip-gray') }}">
                            {{ ucfirst($order->priority) }}
                        </span>
                        @if($order->isMissingBrief())
                            <span class="ds-chip ds-chip-amber">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </span>
                        @elseif($order->attachmentsCount > 0)
                            <span class="ds-chip ds-chip-gray" title="{{ $order->attachmentsCount }} file(s)">
                                <i class="bi bi-paperclip"></i> {{ $order->attachmentsCount }}
                            </span>
                        @endif
                        @if($order->hasNotes)
                            <span class="ds-chip ds-chip-amber" title="PM left internal notes">
                                <i class="bi bi-sticky-fill"></i>
                            </span>
                        @endif
                        {{-- Status dot --}}
                        @if($order->orderStatus === 'in_progress')
                            <span class="ds-chip ds-chip-blue"><i class="bi bi-play-fill"></i> Active</span>
                        @endif
                        <i class="bi bi-chevron-down ds-chevron ms-1"></i>
                    </div>

                </div>

                {{-- Card body: revealed on click --}}
                <div class="ds-card-body" id="ds-body-{{ $order->orderId }}">
                    <div class="row g-2 mb-2">
                        <div class="col-6 col-sm-3">
                            <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px">Order #</div>
                            <div style="font-size:.8rem;font-weight:600">{{ $order->orderNumber }}</div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px">Product</div>
                            <div style="font-size:.8rem;font-weight:600">{{ $order->productTypeLabel }}</div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px">Quantity</div>
                            <div style="font-size:.8rem;font-weight:600">{{ number_format($order->quantity) }} units</div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px">Delivery</div>
                            <div style="font-size:.8rem;font-weight:600">{{ \Carbon\Carbon::parse($order->deliveryDate)->format('d M Y') }}</div>
                        </div>
                    </div>

                    {{-- Brief status chips --}}
                    <div class="d-flex gap-2 flex-wrap mb-1">
                        @if($order->isMissingBrief())
                            <span class="ds-chip ds-chip-amber">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                No brief or spec — contact PM
                            </span>
                        @else
                            @if($order->attachmentsCount > 0)
                                <span class="ds-chip ds-chip-green">
                                    <i class="bi bi-paperclip"></i>
                                    {{ $order->attachmentsCount }} file{{ $order->attachmentsCount !== 1 ? 's' : '' }}
                                </span>
                            @else
                                <span class="ds-chip ds-chip-gray">
                                    <i class="bi bi-paperclip"></i> No files
                                </span>
                            @endif
                            @if($order->hasDetails)
                                <span class="ds-chip ds-chip-blue">
                                    <i class="bi bi-file-text"></i> Spec written
                                </span>
                            @else
                                <span class="ds-chip ds-chip-gray">
                                    <i class="bi bi-file-earmark"></i> No spec
                                </span>
                            @endif
                            @if($order->hasNotes)
                                <span class="ds-chip ds-chip-amber">
                                    <i class="bi bi-sticky-fill"></i> PM notes
                                </span>
                            @endif
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="ds-card-actions">
                        @if(in_array($order->orderStatus, ['pending', 'on_hold']))
                            <form method="POST"
                                  action="{{ route('production.start', ['department' => $order->department, 'orderId' => $order->orderId]) }}"
                                  onclick="event.stopPropagation()">
                                @csrf @method('PATCH')
                                <button type="submit" class="ds-btn-start">
                                    <i class="bi bi-play-fill"></i> Start
                                </button>
                            </form>
                        @endif
                        <form method="POST"
                              action="{{ route('production.complete', ['department' => $order->department, 'orderId' => $order->orderId]) }}"
                              onclick="event.stopPropagation()"
                              onsubmit="return confirm('Mark {{ addslashes($order->whatsappOrderId ?? $order->orderNumber) }} as complete in Design?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="ds-btn-done">
                                <i class="bi bi-check2-all"></i> Mark Done
                            </button>
                        </form>
                        <a href="{{ route('orders.show', $order->orderId) }}"
                           class="ds-btn-view"
                           onclick="event.stopPropagation()">
                            <i class="bi bi-folder2-open"></i> Open Order
                        </a>
                    </div>
                </div>

            </div>
            @endforeach
            </div>
        @endif

    </div>

    {{-- ── Right: sidebar ──────────────────────────────── --}}
    <div class="col-12 col-xl-4">

        {{-- Needs attention --}}
        @php
            $criticalDesign = array_filter($queue->orders, fn($o) => $o->healthStatus === 'red');
        @endphp

        <div class="ds-section"><i class="bi bi-exclamation-octagon-fill text-danger"></i> Needs Immediate Attention</div>

        @if(empty($criticalDesign))
            <div class="ds-empty" style="padding:1.5rem">
                <i class="bi bi-shield-check" style="font-size:1.8rem;color:#22c55e"></i>
                <div class="mt-2" style="font-size:.82rem">All design orders are on track</div>
            </div>
        @else
            @foreach($criticalDesign as $order)
                <a href="{{ route('orders.show', $order->orderId) }}"
                   class="ds-upcoming-item urgent text-decoration-none text-dark mb-2">
                    @if($order->profilePictureUrl)
                        <img src="{{ $order->profilePictureUrl }}" alt="" class="ds-card-avatar">
                    @else
                        <div class="ds-card-avatar-initials">{{ $order->avatarInitials }}</div>
                    @endif
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-semibold text-truncate" style="font-size:.85rem">
                            {{ $order->whatsappOrderId ?? $order->orderNumber }}
                        </div>
                        <div class="text-muted text-truncate" style="font-size:.75rem">
                            {{ $order->customerName }} · {{ number_format($order->quantity) }} {{ $order->productTypeLabel }}
                        </div>
                        @if($order->isMissingBrief())
                            <span class="ds-chip ds-chip-amber mt-1" style="font-size:.65rem">
                                <i class="bi bi-exclamation-triangle-fill"></i> No brief
                            </span>
                        @elseif($order->attachmentsCount > 0)
                            <span class="ds-chip ds-chip-gray mt-1" style="font-size:.65rem">
                                <i class="bi bi-paperclip"></i> {{ $order->attachmentsCount }}
                            </span>
                        @endif
                    </div>
                    <div class="text-end flex-shrink-0">
                        <span class="ds-chip {{ $order->isLate ? 'ds-chip-red' : 'ds-chip-yellow' }}">
                            {{ $order->urgencyLabel() }}
                        </span>
                    </div>
                </a>
            @endforeach
        @endif

        {{-- Upcoming deliveries --}}
        <div class="ds-section mt-4"><i class="bi bi-truck text-primary"></i> Upcoming Deliveries</div>

        @forelse($upcomingOrders->take(8) as $order)
            @php
                $days = $order->days_remaining;
                $urg  = $days <= 0 ? 'urgent' : ($days <= 2 ? 'high' : '');
            @endphp
            <div class="ds-upcoming-item {{ $urg }}">
                @if($order->profile_picture_url)
                    <img src="{{ $order->profile_picture_url }}" alt="" class="ds-card-avatar">
                @else
                    <div class="ds-card-avatar-initials">{{ $order->avatar_initials }}</div>
                @endif
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-semibold text-truncate" style="font-size:.82rem">
                        {{ $order->whatsapp_order_id ?? $order->order_number }}
                    </div>
                    <div class="text-muted text-truncate" style="font-size:.73rem">
                        {{ $order->customer_name }}
                        <span class="ms-1 badge bg-light border text-secondary" style="font-size:.63rem">
                            {{ $order->stage_label }}
                        </span>
                    </div>
                </div>
                <span class="ds-chip flex-shrink-0 {{ $days <= 0 ? 'ds-chip-red' : ($days <= 2 ? 'ds-chip-yellow' : 'ds-chip-green') }}">
                    {{ $days <= 0 ? abs($days) . 'd late' : ($days === 1 ? 'Tomorrow' : $days . 'd') }}
                </span>
            </div>
        @empty
            <div class="ds-empty" style="padding:1.2rem">
                <i class="bi bi-calendar-check" style="font-size:1.5rem"></i>
                <div class="mt-1" style="font-size:.8rem">No deliveries in the next 7 days</div>
            </div>
        @endforelse

    </div>

</div>

@endsection

@push('scripts')
<script>
/* ── Toggle card expand/collapse ─────────────────────────── */
function dsToggleCard(card) {
    const body = card.querySelector('.ds-card-body');
    if (!body) return;
    const isOpen = body.classList.contains('open');
    // Close all
    document.querySelectorAll('.ds-card-body.open').forEach(b => {
        b.classList.remove('open');
        b.closest('.ds-order-card').classList.remove('expanded');
    });
    // Open clicked if it was closed
    if (!isOpen) {
        body.classList.add('open');
        card.classList.add('expanded');
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

/* ── Animated counters ───────────────────────────────────── */
document.querySelectorAll('.ds-counter').forEach(function (el) {
    const target = parseInt(el.dataset.target, 10) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 20));
    const timer = setInterval(function () {
        current = Math.min(current + step, target);
        el.textContent = current;
        if (current >= target) clearInterval(timer);
    }, 40);
});

/* ── Live clock ──────────────────────────────────────────── */
(function clock() {
    const el = document.getElementById('ds-clock');
    if (!el) return;
    function tick() {
        const now = new Date();
        const h   = String(now.getHours()).padStart(2,'0');
        const m   = String(now.getMinutes()).padStart(2,'0');
        const s   = String(now.getSeconds()).padStart(2,'0');
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        el.textContent = days[now.getDay()] + ' ' + h + ':' + m + ':' + s;
    }
    tick();
    setInterval(tick, 1000);
})();

/* ── Progress ring animate on load ──────────────────────── */
(function () {
    const fill = document.getElementById('ds-ring-fill');
    if (!fill) return;
    // Start at full offset (empty) then animate to real value
    const target = parseFloat(fill.getAttribute('stroke-dashoffset'));
    const circ   = parseFloat(fill.getAttribute('stroke-dasharray'));
    fill.style.strokeDashoffset = circ;
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            fill.style.strokeDashoffset = target;
        });
    });
})();
</script>
@endpush
