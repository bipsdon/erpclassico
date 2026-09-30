@extends('layouts.app')

@section('title', 'Color Codes')
@section('page-title')
    <i class="bi bi-palette me-2 text-primary"></i>Color Codes
@endsection

@push('styles')
<style>
/* ── Color card ─────────────────────────────────────────── */
.cc-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s, transform .1s;
    background: #fff;
    user-select: none;
}
.cc-card:hover {
    border-color: #adb5bd;
    box-shadow: 0 4px 16px rgba(0,0,0,.1);
    transform: translateY(-2px);
}
.cc-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.2);
}
.cc-card .swatch {
    height: 100px;
    width: 100%;
    position: relative;
}
.cc-card .check-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #0d6efd;
    color: #fff;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
}
.cc-card.selected .check-badge { display: flex; }
.cc-card .card-info {
    padding: 10px 12px 12px;
    border-top: 1px solid #f1f3f5;
}
.cc-card .card-name {
    font-weight: 600;
    font-size: .88rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 3px;
}
.cc-card .card-hex {
    font-family: monospace;
    font-size: .78rem;
    color: #6c757d;
    margin-bottom: 4px;
}
.cc-card .card-cmyk {
    font-family: monospace;
    font-size: .72rem;
    color: #868e96;
}
/* ── Actions bar ────────────────────────────────────────── */
.cc-card .card-actions {
    display: flex;
    gap: 4px;
    padding: 0 12px 10px;
}
/* ── Selection bar ──────────────────────────────────────── */
#selection-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 1050;
    background: #1a3c5e;
    color: #fff;
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    transform: translateY(100%);
    transition: transform .25s ease;
    box-shadow: 0 -4px 20px rgba(0,0,0,.2);
}
#selection-bar.visible { transform: translateY(0); }
/* ── Preview panel ──────────────────────────────────────── */
#preview-panel {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 1060;
    background: #fff;
    border-top: 2px solid #dee2e6;
    box-shadow: 0 -6px 32px rgba(0,0,0,.15);
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    transform: translateY(100%);
    transition: transform .3s ease;
}
#preview-panel.visible { transform: translateY(0); }
#preview-panel .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #dee2e6;
    flex-shrink: 0;
}
#preview-canvas-wrap {
    flex: 1 1 auto;
    overflow: auto;
    padding: 20px;
    background: #f8f9fa;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}
</style>
@endpush

@section('content')

{{-- ── Pending approvals banner (PM only) ──────────────────────────── --}}
@if(auth()->user()->isPipelineManager() && $pending->isNotEmpty())
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-hourglass-split fs-5 flex-shrink-0"></i>
        <div>
            <strong>{{ $pending->count() }} pending request{{ $pending->count() > 1 ? 's' : '' }}</strong>
            waiting for your approval — see the Pending Requests section below.
        </div>
    </div>
@endif

{{-- ── Page header ──────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="section-title mb-0">
        <i class="bi bi-palette me-2 text-primary"></i>Color Codes
        <span class="text-muted fw-normal ms-1" style="font-size:.78rem">
            — {{ $active->count() }} color(s)
        </span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if(auth()->user()->isDesigner())
            <button id="btn-select-all" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-check2-square me-1"></i>Select All
            </button>
        @endif
        @if(auth()->user()->isPipelineManager())
            <a href="{{ route('color-codes.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Add Color
            </a>
        @elseif(auth()->user()->isDesigner())
            <a href="{{ route('color-codes.request-create') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Request New Color
            </a>
        @endif
    </div>
</div>

{{-- ── Search ───────────────────────────────────────────────────────── --}}
<div class="mb-4" style="max-width:340px">
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" id="color-search" class="form-control border-start-0 ps-0"
               placeholder="Search by name or HEX…" autocomplete="off">
        <button class="btn btn-outline-secondary" id="color-search-clear"
                type="button" style="display:none" title="Clear">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     ACTIVE COLORS — grouped by printer, card layout
══════════════════════════════════════════════════════════════════ --}}
@foreach($printers as $printerKey => $printerLabel)
    @php $group = $byPrinter->get($printerKey, collect()); @endphp

    <div class="mb-5 printer-section" data-printer="{{ $printerKey }}">

        {{-- Printer heading --}}
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark px-3 py-2" style="font-size:.85rem">
                    <i class="bi bi-printer me-1"></i>{{ $printerLabel }}
                </span>
                <span class="text-muted" style="font-size:.82rem">
                    <span class="printer-count">{{ $group->count() }}</span> color(s)
                </span>
            </div>
            @if(auth()->user()->isDesigner() && $group->isNotEmpty())
                <button class="btn btn-outline-secondary btn-sm btn-select-printer"
                        data-printer="{{ $printerKey }}" style="font-size:.75rem">
                    Select {{ $printerLabel }}
                </button>
            @endif
        </div>

        @if($group->isEmpty())
            <div class="text-muted py-3" style="font-size:.85rem">
                <i class="bi bi-palette me-1 opacity-50"></i>
                No colors for {{ $printerLabel }} yet.
            </div>
        @else
            <div class="row g-3 color-card-grid">
                @foreach($group as $entry)
                    @php $hexClean = strtoupper(ltrim($entry->hex, '#')); @endphp
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2 color-card-col"
                         data-search="{{ strtolower($entry->name) }} {{ strtolower($hexClean) }}">

                        <div class="cc-card h-100"
                             data-id="{{ $entry->id }}"
                             data-printer="{{ $printerKey }}"
                             data-printer-label="{{ $printerLabel }}"
                             data-name="{{ $entry->name }}"
                             data-hex="{{ $hexClean }}"
                             data-c="{{ $entry->cyan }}"
                             data-m="{{ $entry->magenta }}"
                             data-y="{{ $entry->yellow }}"
                             data-k="{{ $entry->black }}">

                            {{-- Swatch --}}
                            <div class="swatch" style="background:{{ $entry->hex }}">
                                <div class="check-badge"><i class="bi bi-check"></i></div>
                            </div>

                            {{-- Info --}}
                            <div class="card-info">
                                <div class="card-name" title="{{ $entry->name }}">{{ $entry->name }}</div>
                                <div class="card-hex">#{{ $hexClean }}</div>
                                <div class="card-cmyk">
                                    C:{{ $entry->cyan }} M:{{ $entry->magenta }}
                                    Y:{{ $entry->yellow }} K:{{ $entry->black }}
                                </div>
                            </div>

                            {{-- Actions (PM only — designer uses card tap to select) --}}
                            @if(auth()->user()->isPipelineManager())
                                <div class="card-actions">
                                    <a href="{{ route('color-codes.edit', $entry) }}"
                                       class="btn btn-sm btn-outline-secondary flex-grow-1"
                                       onclick="event.stopPropagation()">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                          action="{{ route('color-codes.destroy', $entry) }}"
                                          class="flex-grow-1"
                                          onclick="event.stopPropagation()"
                                          onsubmit="return confirm('Delete \'{{ addslashes($entry->name) }}\'?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            @elseif(auth()->user()->isDesigner())
                                <div class="card-actions">
                                    <a href="{{ route('color-codes.request-edit', $entry) }}"
                                       class="btn btn-sm btn-outline-secondary flex-grow-1"
                                       onclick="event.stopPropagation()">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('color-codes.request-destroy', $entry) }}"
                                          class="flex-grow-1"
                                          onclick="event.stopPropagation()"
                                          onsubmit="return confirm('Request deletion of \'{{ addslashes($entry->name) }}\'?')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach

{{-- ══════════════════════════════════════════════════════════════════
     PENDING REQUESTS
══════════════════════════════════════════════════════════════════ --}}
@if($pending->isNotEmpty())
    <div class="section-title {{ auth()->user()->isPipelineManager() ? 'text-warning' : '' }} mb-3 mt-2">
        <i class="bi bi-hourglass-split me-2"></i>Pending Requests
        <span class="fw-normal text-muted ms-1" style="font-size:.78rem">— awaiting pipeline manager approval</span>
    </div>

    <div class="card shadow-sm {{ auth()->user()->isPipelineManager() ? 'border-warning' : 'border-0' }}">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="{{ auth()->user()->isPipelineManager() ? 'table-warning' : 'table-light' }}">
                    <tr>
                        <th class="ps-3" style="width:52px">Swatch</th>
                        <th>Name</th>
                        <th class="text-center">Printer</th>
                        <th class="text-center">C</th>
                        <th class="text-center">M</th>
                        <th class="text-center">Y</th>
                        <th class="text-center">K</th>
                        <th class="text-center">Request</th>
                        <th class="text-center d-none d-md-table-cell">By</th>
                        @if(auth()->user()->isPipelineManager())
                            <th class="text-center">Proposed</th>
                            <th class="text-end pe-3">Decision</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($pending as $entry)
                        @php
                            $isPendingEdit   = $entry->status === 'pending_edit';
                            $isPendingDelete = $entry->status === 'pending_delete';
                            $isPendingAdd    = $entry->status === 'pending_add';
                            $badgeClass = match($entry->status) {
                                'pending_add'    => 'bg-success',
                                'pending_edit'   => 'bg-warning text-dark',
                                'pending_delete' => 'bg-danger',
                                default          => 'bg-secondary',
                            };
                            $badgeIcon = match($entry->status) {
                                'pending_add'    => 'bi-plus-circle',
                                'pending_edit'   => 'bi-pencil',
                                'pending_delete' => 'bi-trash3',
                                default          => 'bi-question',
                            };
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <span style="display:inline-block;width:40px;height:26px;
                                             border-radius:5px;border:1px solid rgba(0,0,0,.12);
                                             background:{{ $entry->hex }};
                                             {{ $isPendingDelete ? 'opacity:.35' : '' }}"></span>
                            </td>
                            <td class="fw-semibold {{ $isPendingDelete ? 'text-decoration-line-through text-muted' : '' }}">
                                {{ $entry->name }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-dark" style="font-size:.72rem">{{ $entry->printerLabel }}</span>
                            </td>
                            <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->cyan }}</td>
                            <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->magenta }}</td>
                            <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->yellow }}</td>
                            <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->black }}</td>
                            <td class="text-center">
                                <span class="badge {{ $badgeClass }}" style="font-size:.72rem">
                                    <i class="bi {{ $badgeIcon }} me-1"></i>{{ $entry->statusLabel }}
                                </span>
                            </td>
                            <td class="text-center text-muted d-none d-md-table-cell" style="font-size:.82rem">
                                {{ $entry->creator->name ?? '—' }}
                            </td>
                            @if(auth()->user()->isPipelineManager())
                                <td class="text-center">
                                    @if($isPendingEdit && $entry->pending_hex)
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <span style="display:inline-block;width:34px;height:22px;
                                                         border-radius:4px;border:1px solid rgba(0,0,0,.12);
                                                         background:{{ $entry->pending_hex }}"></span>
                                            <div class="text-start" style="font-size:.72rem;line-height:1.5">
                                                <div class="fw-semibold">{{ $entry->pending_name }}</div>
                                                @if($entry->pending_printer)
                                                    <span class="badge bg-dark" style="font-size:.65rem">
                                                        {{ \App\Models\ColorCode::PRINTERS[$entry->pending_printer] ?? $entry->pending_printer }}
                                                    </span>
                                                @endif
                                                <div class="text-muted" style="font-family:monospace">
                                                    {{ $entry->pending_cyan }}/{{ $entry->pending_magenta }}/{{ $entry->pending_yellow }}/{{ $entry->pending_black }}
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($isPendingAdd)
                                        <span class="text-muted" style="font-size:.75rem">New entry</span>
                                    @elseif($isPendingDelete)
                                        <span class="text-danger" style="font-size:.75rem">Will be removed</span>
                                    @else —
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <form method="POST" action="{{ route('color-codes.approve', $entry) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success me-1">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('color-codes.reject', $entry) }}"
                                          class="d-inline" onsubmit="return confirm('Reject this request?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════
     SELECTION BAR (designer only — slides up from bottom when ≥1 selected)
══════════════════════════════════════════════════════════════════ --}}
@if(auth()->user()->isDesigner())
<div id="selection-bar">
    <div class="d-flex align-items-center gap-3">
        <span class="fw-semibold">
            <span id="sel-count">0</span> color(s) selected
        </span>
        <button id="btn-clear-sel" class="btn btn-sm btn-outline-light">
            <i class="bi bi-x me-1"></i>Clear
        </button>
    </div>
    <div class="d-flex gap-2">
        <button id="btn-open-preview" class="btn btn-light btn-sm fw-semibold">
            <i class="bi bi-eye me-1"></i>Preview &amp; Download
        </button>
    </div>
</div>

{{-- ── Preview / download panel ────────────────────────────── --}}
<div id="preview-panel">
    <div class="panel-header">
        <div class="fw-semibold fs-6">
            <i class="bi bi-palette me-2 text-primary"></i>
            <span id="preview-title">Color Preview</span>
        </div>
        <div class="d-flex gap-2">
            <button id="btn-download" class="btn btn-success btn-sm">
                <i class="bi bi-download me-1"></i>Download PNG
            </button>
            <button id="btn-close-preview" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i> Close
            </button>
        </div>
    </div>
    <div id="preview-canvas-wrap">
        <canvas id="preview-canvas"
                style="border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,.12);max-width:100%">
        </canvas>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════════ --}}
<script>
(function () {

    // ── Live search ──────────────────────────────────────────
    var searchInput = document.getElementById('color-search');
    var clearBtn    = document.getElementById('color-search-clear');

    function doSearch() {
        var q = searchInput.value.trim().toLowerCase().replace(/^#/, '');

        document.querySelectorAll('.printer-section').forEach(function (section) {
            var cols    = section.querySelectorAll('.color-card-col');
            var shown   = 0;
            cols.forEach(function (col) {
                var match = q === '' || col.getAttribute('data-search').indexOf(q) !== -1;
                col.style.display = match ? '' : 'none';
                if (match) shown++;
            });
            var countEl = section.querySelector('.printer-count');
            if (countEl) countEl.textContent = q === '' ? cols.length : shown;
        });

        if (clearBtn) clearBtn.style.display = q !== '' ? '' : 'none';
    }

    if (searchInput) searchInput.addEventListener('input', doSearch);
    if (clearBtn) clearBtn.addEventListener('click', function () {
        searchInput.value = ''; doSearch(); searchInput.focus();
    });

    // ── Selection (designer only) ────────────────────────────
    var selected    = {};
    var selBar      = document.getElementById('selection-bar');
    var selCountEl  = document.getElementById('sel-count');

    if (!selBar) return; // PM has no selection UI — stop here

    function updateBar() {
        var n = Object.keys(selected).length;
        if (selCountEl) selCountEl.textContent = n;
        if (selBar) selBar.classList.toggle('visible', n > 0);
    }

    // Click on any card toggles selection
    document.querySelectorAll('.cc-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            // Don't trigger if they clicked a button/link/form inside the card
            if (e.target.closest('a, button, form')) return;
            var id = card.dataset.id;
            if (selected[id]) {
                delete selected[id];
                card.classList.remove('selected');
            } else {
                selected[id] = {
                    name:         card.dataset.name,
                    hex:          card.dataset.hex,
                    c:            parseInt(card.dataset.c),
                    m:            parseInt(card.dataset.m),
                    y:            parseInt(card.dataset.y),
                    k:            parseInt(card.dataset.k),
                    printer:      card.dataset.printer,
                    printerLabel: card.dataset.printerLabel,
                };
                card.classList.add('selected');
            }
            updateBar();
        });
    });

    // Select all (page-wide)
    var btnAll = document.getElementById('btn-select-all');
    if (btnAll) {
        btnAll.addEventListener('click', function () {
            var allSelected = Object.keys(selected).length === document.querySelectorAll('.cc-card').length;
            document.querySelectorAll('.cc-card').forEach(function (card) {
                var id = card.dataset.id;
                if (allSelected) {
                    delete selected[id];
                    card.classList.remove('selected');
                } else {
                    selected[id] = {
                        name: card.dataset.name, hex: card.dataset.hex,
                        c: parseInt(card.dataset.c), m: parseInt(card.dataset.m),
                        y: parseInt(card.dataset.y), k: parseInt(card.dataset.k),
                        printer: card.dataset.printer, printerLabel: card.dataset.printerLabel,
                    };
                    card.classList.add('selected');
                }
            });
            btnAll.innerHTML = allSelected
                ? '<i class="bi bi-check2-square me-1"></i>Select All'
                : '<i class="bi bi-dash-square me-1"></i>Deselect All';
            updateBar();
        });
    }

    // Select all per printer
    document.querySelectorAll('.btn-select-printer').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var printer = btn.dataset.printer;
            var cards   = document.querySelectorAll('.cc-card[data-printer="' + printer + '"]');
            var allSel  = Array.from(cards).every(function (c) { return !!selected[c.dataset.id]; });
            cards.forEach(function (card) {
                var id = card.dataset.id;
                if (allSel) {
                    delete selected[id];
                    card.classList.remove('selected');
                } else {
                    selected[id] = {
                        name: card.dataset.name, hex: card.dataset.hex,
                        c: parseInt(card.dataset.c), m: parseInt(card.dataset.m),
                        y: parseInt(card.dataset.y), k: parseInt(card.dataset.k),
                        printer: card.dataset.printer, printerLabel: card.dataset.printerLabel,
                    };
                    card.classList.add('selected');
                }
            });
            updateBar();
        });
    });

    // Clear selection
    var btnClear = document.getElementById('btn-clear-sel');
    if (btnClear) {
        btnClear.addEventListener('click', function () {
            document.querySelectorAll('.cc-card.selected').forEach(function (c) { c.classList.remove('selected'); });
            selected = {};
            updateBar();
        });
    }

    // ── Preview panel ────────────────────────────────────────
    var panel   = document.getElementById('preview-panel');
    var canvas  = document.getElementById('preview-canvas');
    var titleEl = document.getElementById('preview-title');

    document.getElementById('btn-open-preview').addEventListener('click', function () {
        renderCanvas();
        panel.classList.add('visible');
        selBar.classList.remove('visible'); // hide bar while panel is open
    });

    document.getElementById('btn-close-preview').addEventListener('click', function () {
        panel.classList.remove('visible');
        if (Object.keys(selected).length > 0) selBar.classList.add('visible');
    });

    // ── Canvas rendering ─────────────────────────────────────
    function renderCanvas() {
        var colors = Object.values(selected);
        var n      = colors.length;
        if (n === 0) return;

        // Group by printer maintaining order
        var groups = {};
        var gOrder = [];
        colors.forEach(function (c) {
            if (!groups[c.printer]) {
                groups[c.printer] = { label: c.printerLabel, items: [] };
                gOrder.push(c.printer);
            }
            groups[c.printer].items.push(c);
        });

        // Layout
        var COLS       = Math.min(n, 5);
        var SW         = 160;   // swatch width
        var SH         = 110;   // swatch height
        var LABEL_H    = 70;    // text below swatch
        var CELL_W     = SW;
        var CELL_H     = SH + LABEL_H;
        var GAP        = 16;
        var PAD        = 28;
        var HDR_H      = 42;

        var canvasW = PAD * 2 + COLS * CELL_W + (COLS - 1) * GAP;

        var totalH = PAD;
        gOrder.forEach(function (pk) {
            var rows = Math.ceil(groups[pk].items.length / COLS);
            totalH += HDR_H + GAP + rows * (CELL_H + GAP) + PAD;
        });

        var DPR = window.devicePixelRatio || 1;
        canvas.width  = canvasW * DPR;
        canvas.height = totalH  * DPR;
        canvas.style.width  = canvasW + 'px';
        canvas.style.height = totalH  + 'px';

        var ctx = canvas.getContext('2d');
        ctx.scale(DPR, DPR);

        // Background
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, canvasW, totalH);

        var curY = PAD;

        gOrder.forEach(function (pk) {
            var grp   = groups[pk];
            var items = grp.items;

            // Printer header bar
            ctx.fillStyle = '#1a3c5e';
            roundRect(ctx, PAD, curY, canvasW - PAD * 2, HDR_H, 8);
            ctx.fill();
            ctx.fillStyle    = '#ffffff';
            ctx.font         = 'bold 14px system-ui,sans-serif';
            ctx.textBaseline = 'middle';
            ctx.fillText('Printer: ' + grp.label, PAD + 16, curY + HDR_H / 2);
            curY += HDR_H + GAP;

            // Color swatches
            items.forEach(function (item, i) {
                var col  = i % COLS;
                var row  = Math.floor(i / COLS);
                var x    = PAD + col * (CELL_W + GAP);
                var y    = curY + row * (CELL_H + GAP);
                var hex  = '#' + item.hex;

                // Card background
                ctx.fillStyle = '#ffffff';
                roundRect(ctx, x, y, CELL_W, CELL_H, 8);
                ctx.fill();

                // Swatch
                ctx.fillStyle = hex;
                roundRect(ctx, x, y, CELL_W, SH, 8);
                ctx.fill();
                // Bottom corners of swatch square (no radius)
                ctx.fillRect(x, y + SH - 8, CELL_W, 8);

                // Card border
                ctx.strokeStyle = 'rgba(0,0,0,.1)';
                ctx.lineWidth   = 1;
                roundRect(ctx, x, y, CELL_W, CELL_H, 8);
                ctx.stroke();

                // Text
                var ty = y + SH + 8;
                ctx.fillStyle    = '#111827';
                ctx.font         = 'bold 12px system-ui,sans-serif';
                ctx.textBaseline = 'top';
                // Truncate name if too long
                var maxW = CELL_W - 10;
                var name = item.name;
                while (ctx.measureText(name).width > maxW && name.length > 3) {
                    name = name.slice(0, -1);
                }
                if (name !== item.name) name += '…';
                ctx.fillText(name, x + 6, ty);

                ctx.fillStyle = '#6b7280';
                ctx.font      = '11px ui-monospace,monospace';
                ctx.fillText('#' + item.hex.toUpperCase(), x + 6, ty + 16);
                ctx.fillText(
                    'C' + item.c + ' M' + item.m + ' Y' + item.y + ' K' + item.k,
                    x + 6, ty + 30
                );
            });

            var rows = Math.ceil(items.length / COLS);
            curY += rows * (CELL_H + GAP) + PAD;
        });

        if (titleEl) titleEl.textContent =
            n + ' color' + (n !== 1 ? 's' : '') + ' selected';
    }

    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.arcTo(x + w, y,     x + w, y + r,     r);
        ctx.lineTo(x + w, y + h - r);
        ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
        ctx.lineTo(x + r, y + h);
        ctx.arcTo(x,     y + h, x,     y + h - r, r);
        ctx.lineTo(x,     y + r);
        ctx.arcTo(x,     y,     x + r, y,         r);
        ctx.closePath();
    }

    // ── Download ─────────────────────────────────────────────
    document.getElementById('btn-download').addEventListener('click', function () {
        renderCanvas();
        var a      = document.createElement('a');
        a.download = 'color-palette-' + new Date().toISOString().slice(0, 10) + '.png';
        a.href     = canvas.toDataURL('image/png');
        a.click();
    });

})();
</script>

@endsection
