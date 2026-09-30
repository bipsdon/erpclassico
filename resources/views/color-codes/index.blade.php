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

{{-- ── Live search ──────────────────────────────────────────────────── --}}
<div class="mb-4" style="max-width:340px">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
        <input type="text"
               id="color-search"
               class="form-control"
               placeholder="Search by name or HEX…"
               autocomplete="off">
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
    @php
        $group = $byPrinter->get($printerKey, collect());
    @endphp

    <div class="mb-5 printer-group" data-printer="{{ $printerKey }}">

        {{-- Printer heading --}}
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
                            <th class="ps-3" style="width:56px">Swatch</th>
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
                                    <span style="
                                        display:inline-block;
                                        width:44px;height:28px;
                                        border-radius:5px;
                                        border:1px solid rgba(0,0,0,.15);
                                        background:{{ $entry->hex }}">
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $entry->name }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->cyan }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->magenta }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->yellow }}</td>
                                <td class="text-center" style="font-family:monospace;font-size:.85rem">{{ $entry->black }}</td>
                                <td class="text-center">
                                    <span style="font-family:monospace;font-size:.82rem;background:#f8f9fa;padding:2px 7px;border-radius:4px;border:1px solid #dee2e6">
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
                                        <form method="POST"
                                              action="{{ route('color-codes.destroy', $entry) }}"
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
                                        <form method="POST"
                                              action="{{ route('color-codes.request-destroy', $entry) }}"
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
                                <td colspan="9" class="text-center text-muted py-4" style="font-size:.85rem">
                                    <i class="bi bi-palette d-block fs-3 mb-1 opacity-25"></i>
                                    No colors for {{ $printerLabel }} yet.
                                </td>
                            </tr>
                        @endforelse

                        {{-- shown by JS when search has no matches in this group --}}
                        @if($group->isNotEmpty())
                            <tr class="search-empty-row" style="display:none">
                                <td colspan="9" class="text-center text-muted py-3" style="font-size:.85rem">
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
                                <span style="
                                    display:inline-block;width:44px;height:28px;
                                    border-radius:5px;border:1px solid rgba(0,0,0,.15);
                                    background:{{ $entry->hex }};
                                    {{ $isPendingDelete ? 'opacity:.4' : '' }}">
                                </span>
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
                                            <span style="
                                                display:inline-block;width:36px;height:24px;
                                                border-radius:4px;border:1px solid rgba(0,0,0,.15);
                                                background:{{ $entry->pending_hex }}">
                                            </span>
                                            <div class="text-start" style="font-size:.72rem;line-height:1.5">
                                                <div class="fw-semibold">{{ $entry->pending_name }}</div>
                                                @if($entry->pending_printer)
                                                    <div>
                                                        <span class="badge bg-dark" style="font-size:.65rem">
                                                            {{ \App\Models\ColorCode::PRINTERS[$entry->pending_printer] ?? $entry->pending_printer }}
                                                        </span>
                                                    </div>
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
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <form method="POST"
                                          action="{{ route('color-codes.approve', $entry) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success me-1">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('color-codes.reject', $entry) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Reject this request?')">
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

<script>
(function () {
    var searchInput = document.getElementById('color-search');
    var clearBtn    = document.getElementById('color-search-clear');
    var countEl     = document.getElementById('color-count');
    var totalCount  = parseInt(countEl ? countEl.textContent : '0');

    if (!searchInput) return;

    function doSearch() {
        var q     = searchInput.value.trim().toLowerCase().replace(/^#/, '');
        var total = 0;

        document.querySelectorAll('.printer-group').forEach(function (group) {
            var rows      = group.querySelectorAll('tbody tr[data-search]');
            var shown     = 0;
            var emptyBase = group.querySelector('tbody tr.empty-row');
            var emptySearch = group.querySelector('tbody tr.search-empty-row');

            rows.forEach(function (row) {
                var match = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) shown++;
            });

            total += shown;

            // Update per-group count
            var countSpan = group.querySelector('.printer-count');
            if (countSpan) countSpan.textContent = q === '' ? rows.length : shown;

            // Toggle empty states
            if (emptyBase)   emptyBase.style.display   = (rows.length === 0) ? '' : 'none';
            if (emptySearch) emptySearch.style.display = (rows.length > 0 && shown === 0) ? '' : 'none';
        });

        if (countEl) countEl.textContent = q === '' ? totalCount : total;
        if (clearBtn) clearBtn.style.display = q !== '' ? '' : 'none';
    }

    searchInput.addEventListener('input', doSearch);

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            doSearch();
            searchInput.focus();
        });
    }
})();
</script>

@endsection
