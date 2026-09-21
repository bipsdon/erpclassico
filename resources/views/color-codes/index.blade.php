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

{{-- ── Header row ───────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="section-title mb-0">
        <i class="bi bi-palette me-2 text-primary"></i>Active Color Codes
        <span class="text-muted fw-normal ms-1" style="font-size:.78rem">— <span id="color-count">{{ $active->count() }}</span> color(s)</span>
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
<div class="mb-3" style="max-width:340px">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
        <input type="text"
               id="color-search"
               class="form-control"
               placeholder="Search by name or HEX…"
               autocomplete="off">
        <button class="btn btn-outline-secondary" id="color-search-clear" type="button" style="display:none" title="Clear">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-5">
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
                    <th class="text-center d-none d-md-table-cell">Added by</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody id="color-table-body">
                @forelse($active as $entry)
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
                                      onsubmit="return confirm('Delete \'{{ addslashes($entry->name) }}\'? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            @elseif(auth()->user()->isDesigner())
                                <a href="{{ route('color-codes.request-edit', $entry) }}"
                                   class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="bi bi-pencil"></i> Request Edit
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
                    <tr id="color-empty-row">
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-palette d-block fs-2 mb-2 opacity-25"></i>
                            No active color codes yet.
                            @if(auth()->user()->isPipelineManager())
                                <a href="{{ route('color-codes.create') }}" class="ms-1">Add the first one.</a>
                            @else
                                <a href="{{ route('color-codes.request-create') }}" class="ms-1">Submit a request.</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                {{-- shown only by JS when search has no matches --}}
                @if($active->isNotEmpty())
                    <tr id="color-empty-row" style="display:none">
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-search d-block fs-3 mb-1 opacity-25"></i>
                            No colors match your search.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ── Pending requests ─────────────────────────────────────────────── --}}
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
                                    display:inline-block;
                                    width:44px;height:28px;
                                    border-radius:5px;
                                    border:1px solid rgba(0,0,0,.15);
                                    background:{{ $entry->hex }};
                                    {{ $isPendingDelete ? 'opacity:.4' : '' }}">
                                </span>
                            </td>
                            <td class="fw-semibold {{ $isPendingDelete ? 'text-decoration-line-through text-muted' : '' }}">
                                {{ $entry->name }}
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
                                                display:inline-block;
                                                width:36px;height:24px;
                                                border-radius:4px;
                                                border:1px solid rgba(0,0,0,.15);
                                                background:{{ $entry->pending_hex }}">
                                            </span>
                                            <div class="text-start" style="font-size:.72rem;line-height:1.5">
                                                <div class="fw-semibold">{{ $entry->pending_name }}</div>
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
                                        <button type="submit" class="btn btn-sm btn-success me-1" title="Approve">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('color-codes.reject', $entry) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Reject this request?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject">
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
    var searchInput  = document.getElementById('color-search');
    var clearBtn     = document.getElementById('color-search-clear');
    var countEl      = document.getElementById('color-count');
    var totalCount   = parseInt(countEl ? countEl.textContent : '0');

    if (!searchInput) return;

    function doSearch() {
        var q    = searchInput.value.trim().toLowerCase().replace(/^#/, '');
        var rows = document.querySelectorAll('#color-table-body tr[data-search]');
        var shown = 0;

        rows.forEach(function (row) {
            var hay = row.getAttribute('data-search');
            var match = q === '' || hay.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) shown++;
        });

        if (countEl) countEl.textContent = q === '' ? totalCount : shown;
        if (clearBtn) clearBtn.style.display = q !== '' ? '' : 'none';

        // Show/hide empty-state row
        var emptyRow = document.getElementById('color-empty-row');
        if (emptyRow) emptyRow.style.display = (shown === 0 && q !== '') ? '' : 'none';
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
