@extends('layouts.app')

@section('title', 'Color Codes')
@section('page-title')
    <i class="bi bi-palette me-2 text-primary"></i>Color Codes
@endsection

@section('content')

{{-- ── Pending approvals banner (PM only) ──────────────────────────── --}}
@if(auth()->user()->isPipelineManager() && $pending->isNotEmpty())
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-hourglass-split fs-5 flex-shrink-0"></i>
        <div>
            <strong>{{ $pending->count() }} pending request{{ $pending->count() > 1 ? 's' : '' }}</strong>
            from designer{{ $pending->count() > 1 ? 's' : '' }} — see the Pending Requests table below.
        </div>
    </div>
@endif

{{-- ── Header + Add button ──────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="section-title mb-0">
        <i class="bi bi-palette me-2 text-primary"></i>Color Codes
        <span class="text-muted fw-normal ms-1" style="font-size:.78rem">
            — <span id="color-count">{{ $active->count() }}</span> color(s)
        </span>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        {{-- Preview button — visible when at least one color is selected --}}
        <button id="btn-open-preview" class="btn btn-outline-primary btn-sm" style="display:none">
            <i class="bi bi-eye me-1"></i>Preview Selected (<span id="sel-count">0</span>)
        </button>

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

{{-- ── Live search ──────────────────────────────────────────────────── --}}
<div class="mb-4" style="max-width:340px">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="color-search" class="form-control"
               placeholder="Search by name or HEX…" autocomplete="off">
        <button class="btn btn-outline-secondary" id="color-search-clear"
                type="button" style="display:none" title="Clear">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     ACTIVE COLORS — grouped by printer
══════════════════════════════════════════════════════════════════ --}}
@foreach($printers as $printerKey => $printerLabel)
    @php $group = $byPrinter->get($printerKey, collect()); @endphp

    <div class="mb-5 printer-group" data-printer="{{ $printerKey }}">

        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-dark fs-6 px-3 py-2">
                <i class="bi bi-printer me-1"></i>{{ $printerLabel }}
            </span>
            <span class="text-muted" style="font-size:.82rem">
                — <span class="printer-count">{{ $group->count() }}</span> color(s)
            </span>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            {{-- Select-all checkbox --}}
                            <th class="ps-3" style="width:36px">
                                <input type="checkbox" class="form-check-input select-all-printer"
                                       data-printer="{{ $printerKey }}" title="Select all {{ $printerLabel }}">
                            </th>
                            <th style="width:56px">Swatch</th>
                            <th>Color Name</th>
                            <th class="text-center">C</th>
                            <th class="text-center">M</th>
                            <th class="text-center">Y</th>
                            <th class="text-center">K</th>
                            <th class="text-center">HEX</th>
                            <th class="text-center d-none d-md-table-cell">Added by</th>
                            @if(auth()->user()->isPipelineManager())
                                <th class="text-end pe-3">Actions</th>
                            @else
                                <th class="text-end pe-3">Request</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="color-table-body">
                        @forelse($group as $entry)
                            <tr data-search="{{ strtolower($entry->name) }} {{ strtolower(ltrim($entry->hex, '#')) }}">
                                <td class="ps-3">
                                    <input type="checkbox"
                                           class="form-check-input color-select"
                                           data-id="{{ $entry->id }}"
                                           data-printer="{{ $printerKey }}"
                                           data-printer-label="{{ $printerLabel }}"
                                           data-name="{{ $entry->name }}"
                                           data-hex="{{ strtoupper(ltrim($entry->hex,'#')) }}"
                                           data-c="{{ $entry->cyan }}"
                                           data-m="{{ $entry->magenta }}"
                                           data-y="{{ $entry->yellow }}"
                                           data-k="{{ $entry->black }}">
                                </td>
                                <td>
                                    <span style="display:inline-block;width:44px;height:28px;border-radius:5px;
                                                 border:1px solid rgba(0,0,0,.15);background:{{ $entry->hex }}"></span>
                                </td>
                                <td class="fw-semibold">{{ $entry->name }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->cyan }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->magenta }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->yellow }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->black }}</td>
                                <td class="text-center">
                                    <span style="font-family:monospace;font-size:.82rem;background:#f8f9fa;
                                                 padding:2px 7px;border-radius:4px;border:1px solid #dee2e6">
                                        {{ strtoupper($entry->hex) }}
                                    </span>
                                </td>
                                <td class="text-center text-muted d-none d-md-table-cell" style="font-size:.82rem">
                                    {{ $entry->creator->name ?? '—' }}
                                </td>
                                <td class="text-end pe-3">
                                    @if(auth()->user()->isPipelineManager())
                                        <a href="{{ route('color-codes.edit', $entry) }}"
                                           class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('color-codes.destroy', $entry) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete \'{{ addslashes($entry->name) }}\'?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @elseif(auth()->user()->isDesigner())
                                        <a href="{{ route('color-codes.request-edit', $entry) }}"
                                           class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('color-codes.request-destroy', $entry) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Request deletion of \'{{ addslashes($entry->name) }}\'?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="10" class="text-center text-muted py-4" style="font-size:.85rem">
                                    <i class="bi bi-palette d-block fs-3 mb-1 opacity-25"></i>
                                    No colors for {{ $printerLabel }} yet.
                                </td>
                            </tr>
                        @endforelse
                        @if($group->isNotEmpty())
                            <tr class="search-empty-row" style="display:none">
                                <td colspan="10" class="text-center text-muted py-3" style="font-size:.85rem">
                                    <i class="bi bi-search me-1"></i>No colors match your search in {{ $printerLabel }}.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach

{{-- ══════════════════════════════════════════════════════════════════
     PENDING REQUESTS
══════════════════════════════════════════════════════════════════ --}}
@if($pending->isNotEmpty())
    <div class="section-title {{ auth()->user()->isPipelineManager() ? 'text-warning' : '' }} mb-3">
        <i class="bi bi-hourglass-split me-2"></i>Pending Requests
        <span class="fw-normal text-muted ms-1" style="font-size:.78rem">— awaiting pipeline manager approval</span>
    </div>

    <div class="card shadow-sm {{ auth()->user()->isPipelineManager() ? 'border-warning' : 'border-0' }}">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="{{ auth()->user()->isPipelineManager() ? 'table-warning' : 'table-light' }}">
                    <tr>
                        <th class="ps-3" style="width:56px">Swatch</th>
                        <th>Color Name</th>
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
                                <span style="display:inline-block;width:44px;height:28px;border-radius:5px;
                                             border:1px solid rgba(0,0,0,.15);background:{{ $entry->hex }};
                                             {{ $isPendingDelete ? 'opacity:.4' : '' }}"></span>
                            </td>
                            <td class="fw-semibold {{ $isPendingDelete ? 'text-decoration-line-through text-muted' : '' }}">
                                {{ $entry->name }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-dark" style="font-size:.72rem">{{ $entry->printerLabel }}</span>
                            </td>
                            <td class="text-center {{ $isPendingDelete ? 'text-muted' : '' }}" style="font-family:monospace;font-size:.85rem">{{ $entry->cyan }}</td>
                            <td class="text-center {{ $isPendingDelete ? 'text-muted' : '' }}" style="font-family:monospace;font-size:.85rem">{{ $entry->magenta }}</td>
                            <td class="text-center {{ $isPendingDelete ? 'text-muted' : '' }}" style="font-family:monospace;font-size:.85rem">{{ $entry->yellow }}</td>
                            <td class="text-center {{ $isPendingDelete ? 'text-muted' : '' }}" style="font-family:monospace;font-size:.85rem">{{ $entry->black }}</td>
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
                                            <span style="display:inline-block;width:36px;height:24px;border-radius:4px;
                                                         border:1px solid rgba(0,0,0,.15);background:{{ $entry->pending_hex }}"></span>
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
     PREVIEW PANEL (slides up from bottom when colors are selected)
══════════════════════════════════════════════════════════════════ --}}
<div id="preview-panel" style="
    position:fixed;bottom:0;left:0;right:0;z-index:1050;
    background:#fff;border-top:2px solid #dee2e6;
    box-shadow:0 -4px 24px rgba(0,0,0,.12);
    transform:translateY(100%);transition:transform .3s ease;
    max-height:85vh;display:flex;flex-direction:column">

    {{-- Panel header --}}
    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom flex-shrink-0">
        <div class="fw-semibold fs-6">
            <i class="bi bi-eye me-2 text-primary"></i>
            Color Preview — <span id="preview-title">0 colors selected</span>
        </div>
        <div class="d-flex gap-2">
            <button id="btn-download" class="btn btn-success btn-sm">
                <i class="bi bi-download me-1"></i>Download PNG
            </button>
            <button id="btn-close-preview" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    {{-- Canvas lives here --}}
    <div class="flex-grow-1 overflow-auto p-3 d-flex align-items-start justify-content-center bg-light">
        <canvas id="preview-canvas" style="max-width:100%;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.1)"></canvas>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════════ --}}
<script>
(function () {

    // ── Live search ──────────────────────────────────────────
    var searchInput = document.getElementById('color-search');
    var clearBtn    = document.getElementById('color-search-clear');
    var countEl     = document.getElementById('color-count');
    var totalCount  = parseInt(countEl ? countEl.textContent : '0');

    function doSearch() {
        var q     = searchInput.value.trim().toLowerCase().replace(/^#/, '');
        var total = 0;

        document.querySelectorAll('.printer-group').forEach(function (group) {
            var rows        = group.querySelectorAll('tbody tr[data-search]');
            var shown       = 0;
            var emptyBase   = group.querySelector('tbody tr.empty-row');
            var emptySearch = group.querySelector('tbody tr.search-empty-row');

            rows.forEach(function (row) {
                var match = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) shown++;
            });
            total += shown;

            var cs = group.querySelector('.printer-count');
            if (cs) cs.textContent = q === '' ? rows.length : shown;
            if (emptyBase)   emptyBase.style.display   = rows.length === 0 ? '' : 'none';
            if (emptySearch) emptySearch.style.display = rows.length > 0 && shown === 0 ? '' : 'none';
        });

        if (countEl)  countEl.textContent = q === '' ? totalCount : total;
        if (clearBtn) clearBtn.style.display = q !== '' ? '' : 'none';
    }

    if (searchInput) searchInput.addEventListener('input', doSearch);
    if (clearBtn) clearBtn.addEventListener('click', function () {
        searchInput.value = ''; doSearch(); searchInput.focus();
    });

    // ── Selection tracking ───────────────────────────────────
    var selected = {};   // id → {name, hex, c, m, y, k, printer, printerLabel}

    function syncSelectionUI() {
        var count = Object.keys(selected).length;
        var btn   = document.getElementById('btn-open-preview');
        var sc    = document.getElementById('sel-count');
        if (btn) btn.style.display = count > 0 ? '' : 'none';
        if (sc)  sc.textContent    = count;
    }

    // Individual checkboxes
    document.querySelectorAll('.color-select').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var id = cb.dataset.id;
            if (cb.checked) {
                selected[id] = {
                    name:         cb.dataset.name,
                    hex:          cb.dataset.hex,
                    c:            parseInt(cb.dataset.c),
                    m:            parseInt(cb.dataset.m),
                    y:            parseInt(cb.dataset.y),
                    k:            parseInt(cb.dataset.k),
                    printer:      cb.dataset.printer,
                    printerLabel: cb.dataset.printerLabel,
                };
            } else {
                delete selected[id];
                // Uncheck the select-all for this printer if it was checked
                var sa = document.querySelector('.select-all-printer[data-printer="' + cb.dataset.printer + '"]');
                if (sa) sa.checked = false;
            }
            syncSelectionUI();
        });
    });

    // Select-all per printer
    document.querySelectorAll('.select-all-printer').forEach(function (sa) {
        sa.addEventListener('change', function () {
            var printer = sa.dataset.printer;
            document.querySelectorAll('.color-select[data-printer="' + printer + '"]')
                .forEach(function (cb) {
                    cb.checked = sa.checked;
                    var id = cb.dataset.id;
                    if (sa.checked) {
                        selected[id] = {
                            name:         cb.dataset.name,
                            hex:          cb.dataset.hex,
                            c:            parseInt(cb.dataset.c),
                            m:            parseInt(cb.dataset.m),
                            y:            parseInt(cb.dataset.y),
                            k:            parseInt(cb.dataset.k),
                            printer:      cb.dataset.printer,
                            printerLabel: cb.dataset.printerLabel,
                        };
                    } else {
                        delete selected[id];
                    }
                });
            syncSelectionUI();
        });
    });

    // ── Preview panel ────────────────────────────────────────
    var panel   = document.getElementById('preview-panel');
    var canvas  = document.getElementById('preview-canvas');
    var titleEl = document.getElementById('preview-title');

    function openPreview() {
        renderCanvas();
        panel.style.transform = 'translateY(0)';
    }

    function closePreview() {
        panel.style.transform = 'translateY(100%)';
    }

    document.getElementById('btn-open-preview').addEventListener('click', openPreview);
    document.getElementById('btn-close-preview').addEventListener('click', closePreview);

    // ── Canvas rendering ─────────────────────────────────────
    function renderCanvas() {
        var colors = Object.values(selected);
        var count  = colors.length;

        if (count === 0) return;

        // Group by printer
        var groups = {};
        colors.forEach(function (c) {
            if (!groups[c.printer]) groups[c.printer] = { label: c.printerLabel, items: [] };
            groups[c.printer].items.push(c);
        });
        var groupKeys = Object.keys(groups);

        // Layout constants
        var COLS        = Math.min(count, 4);     // max 4 per row
        var SWATCH_W    = 180;
        var SWATCH_H    = 120;
        var LABEL_H     = 72;                     // text block below each swatch
        var CELL_W      = SWATCH_W + 20;          // swatch + margin
        var CELL_H      = SWATCH_H + LABEL_H + 16;
        var GROUP_HDR_H = 44;                     // printer heading row height
        var PAD         = 32;                     // outer padding
        var GAP         = 16;                     // gap between cells

        // Calculate total canvas height
        var totalH = PAD;
        groupKeys.forEach(function (pk) {
            var items  = groups[pk].items;
            var rows   = Math.ceil(items.length / COLS);
            totalH += GROUP_HDR_H + rows * (CELL_H + GAP) + PAD;
        });

        var totalW = PAD * 2 + COLS * CELL_W + (COLS - 1) * GAP;

        var DPR  = window.devicePixelRatio || 1;
        canvas.width  = totalW * DPR;
        canvas.height = totalH * DPR;
        canvas.style.width  = totalW + 'px';
        canvas.style.height = totalH + 'px';

        var ctx = canvas.getContext('2d');
        ctx.scale(DPR, DPR);

        // White background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, totalW, totalH);

        var curY = PAD;

        groupKeys.forEach(function (pk) {
            var grp   = groups[pk];
            var items = grp.items;

            // Group heading
            ctx.fillStyle = '#1a3c5e';
            ctx.fillRect(PAD, curY, totalW - PAD * 2, GROUP_HDR_H);
            ctx.fillStyle = '#ffffff';
            ctx.font      = 'bold 16px system-ui, sans-serif';
            ctx.textBaseline = 'middle';
            ctx.fillText('\uD83D\uDDA8  ' + grp.label, PAD + 16, curY + GROUP_HDR_H / 2);
            curY += GROUP_HDR_H + GAP;

            // Color cells
            for (var i = 0; i < items.length; i++) {
                var col  = i % COLS;
                var row  = Math.floor(i / COLS);
                var x    = PAD + col * (CELL_W + GAP);
                var y    = curY + row * (CELL_H + GAP);
                var item = items[i];
                var hex  = '#' + item.hex;

                // Swatch rectangle with rounded corners
                ctx.fillStyle = hex;
                roundRect(ctx, x, y, SWATCH_W, SWATCH_H, 8);
                ctx.fill();

                // Thin border
                ctx.strokeStyle = 'rgba(0,0,0,0.12)';
                ctx.lineWidth   = 1;
                roundRect(ctx, x, y, SWATCH_W, SWATCH_H, 8);
                ctx.stroke();

                // Text block
                var ty = y + SWATCH_H + 10;

                ctx.fillStyle    = '#111827';
                ctx.font         = 'bold 13px system-ui, sans-serif';
                ctx.textBaseline = 'top';
                ctx.fillText(item.name, x, ty);

                ctx.fillStyle = '#6b7280';
                ctx.font      = '11px monospace, monospace';
                ctx.fillText('#' + item.hex.toUpperCase(), x, ty + 18);
                ctx.fillText(
                    'C:' + item.c + '  M:' + item.m + '  Y:' + item.y + '  K:' + item.k,
                    x, ty + 33
                );
            }

            var rows = Math.ceil(items.length / COLS);
            curY += rows * (CELL_H + GAP) + PAD / 2;
        });

        // Update title
        if (titleEl) titleEl.textContent = count + ' color' + (count > 1 ? 's' : '') + ' selected';
    }

    // ── Rounded rect helper ──────────────────────────────────
    function roundRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    // ── Download ─────────────────────────────────────────────
    document.getElementById('btn-download').addEventListener('click', function () {
        if (!canvas.width) { renderCanvas(); }
        var link      = document.createElement('a');
        link.download = 'color-palette-' + new Date().toISOString().slice(0,10) + '.png';
        link.href     = canvas.toDataURL('image/png');
        link.click();
    });

})();
</script>

@endsection
