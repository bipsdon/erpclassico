@extends('layouts.app')

@section('title', 'Color Codes')
@section('page-title')
    <i class="bi bi-palette me-2 text-primary"></i>Color Codes
@endsection

@push('styles')
<style>
    .color-swatch {
        width: 40px;
        height: 28px;
        border-radius: 5px;
        border: 1px solid rgba(0,0,0,.15);
        display: inline-block;
        flex-shrink: 0;
    }
    .cmyk-chip {
        font-size: .72rem;
        font-family: monospace;
        letter-spacing: .3px;
    }
    /* Modal input CMYK live preview box */
    #preview-swatch,
    #edit-preview-swatch,
    #req-preview-swatch,
    #req-edit-preview-swatch {
        width: 56px;
        height: 38px;
        border-radius: 6px;
        border: 1px solid rgba(0,0,0,.2);
        transition: background .15s;
    }
</style>
@endpush

@section('content')

{{-- ── Pending approvals banner (PM only) ─────────────────── --}}
@if(auth()->user()->isPipelineManager() && $pending->isNotEmpty())
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-hourglass-split fs-5"></i>
        <div>
            <strong>{{ $pending->count() }} pending request{{ $pending->count() > 1 ? 's' : '' }}</strong>
            waiting for your approval — scroll down to the Pending Requests section.
        </div>
    </div>
@endif

{{-- ── Active color codes ──────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="section-title mb-0">
        <i class="bi bi-palette me-2 text-primary"></i>Active Color Codes
        <span class="text-muted fw-normal ms-1" style="font-size:.78rem">— {{ $active->count() }} color(s)</span>
    </div>

    {{-- Add button --}}
    @if(auth()->user()->isPipelineManager())
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addColorModal">
            <i class="bi bi-plus-lg me-1"></i>Add Color
        </button>
    @elseif(auth()->user()->isDesigner())
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reqAddColorModal">
            <i class="bi bi-plus-lg me-1"></i>Request New Color
        </button>
    @endif
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:44px">Color</th>
                    <th>Name</th>
                    <th class="text-center">C</th>
                    <th class="text-center">M</th>
                    <th class="text-center">Y</th>
                    <th class="text-center">K</th>
                    <th class="text-center d-none d-md-table-cell">Added by</th>
                    <th class="text-end pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($active as $color)
                    <tr>
                        <td class="ps-3">
                            <span class="color-swatch" style="background:{{ $color->hex }}"></span>
                        </td>
                        <td class="fw-semibold">{{ $color->name }}</td>
                        <td class="text-center cmyk-chip">{{ $color->cyan }}</td>
                        <td class="text-center cmyk-chip">{{ $color->magenta }}</td>
                        <td class="text-center cmyk-chip">{{ $color->yellow }}</td>
                        <td class="text-center cmyk-chip">{{ $color->black }}</td>
                        <td class="text-center text-muted d-none d-md-table-cell" style="font-size:.8rem">
                            {{ $color->creator->name ?? '—' }}
                        </td>
                        <td class="text-end pe-3">
                            @if(auth()->user()->isPipelineManager())
                                {{-- PM: inline edit & delete --}}
                                <button class="btn btn-sm btn-outline-secondary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editColorModal"
                                        data-id="{{ $color->id }}"
                                        data-name="{{ $color->name }}"
                                        data-c="{{ $color->cyan }}"
                                        data-m="{{ $color->magenta }}"
                                        data-y="{{ $color->yellow }}"
                                        data-k="{{ $color->black }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('color-codes.destroy', $color) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete \'{{ $color->name }}\'?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            @elseif(auth()->user()->isDesigner())
                                {{-- Designer: request edit / request delete --}}
                                <button class="btn btn-sm btn-outline-secondary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reqEditColorModal"
                                        data-id="{{ $color->id }}"
                                        data-name="{{ $color->name }}"
                                        data-c="{{ $color->cyan }}"
                                        data-m="{{ $color->magenta }}"
                                        data-y="{{ $color->yellow }}"
                                        data-k="{{ $color->black }}">
                                    <i class="bi bi-pencil"></i> Request Edit
                                </button>
                                <form method="POST"
                                      action="{{ route('color-codes.request-destroy', $color) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Request deletion of \'{{ $color->name }}\'?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-palette d-block fs-2 mb-2 opacity-25"></i>
                            No active color codes yet.
                            @if(auth()->user()->isPipelineManager())
                                Click <strong>Add Color</strong> to get started.
                            @else
                                Click <strong>Request New Color</strong> to submit one for approval.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Pending requests (PM sees full table; designer sees their own) ── --}}
@if($pending->isNotEmpty())
    <div class="section-title {{ auth()->user()->isPipelineManager() ? 'text-warning' : '' }}">
        <i class="bi bi-hourglass-split me-2"></i>Pending Requests
        <span class="fw-normal text-muted ms-1" style="font-size:.78rem">— awaiting pipeline manager approval</span>
    </div>

    <div class="card shadow-sm {{ auth()->user()->isPipelineManager() ? 'border-warning' : 'border-0' }}">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="{{ auth()->user()->isPipelineManager() ? 'table-warning' : 'table-light' }}">
                    <tr>
                        <th class="ps-3" style="width:44px">Color</th>
                        <th>Name</th>
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
                    @foreach($pending as $color)
                        @php
                            $isPendingEdit   = $color->status === 'pending_edit';
                            $isPendingDelete = $color->status === 'pending_delete';
                            $isPendingAdd    = $color->status === 'pending_add';
                        @endphp
                        <tr>
                            {{-- Current swatch --}}
                            <td class="ps-3">
                                <span class="color-swatch" style="background:{{ $color->hex }}"></span>
                            </td>
                            {{-- Current name (strikethrough if pending delete) --}}
                            <td class="fw-semibold {{ $isPendingDelete ? 'text-decoration-line-through text-muted' : '' }}">
                                {{ $color->name }}
                            </td>
                            <td class="text-center cmyk-chip {{ $isPendingDelete ? 'text-muted' : '' }}">{{ $color->cyan }}</td>
                            <td class="text-center cmyk-chip {{ $isPendingDelete ? 'text-muted' : '' }}">{{ $color->magenta }}</td>
                            <td class="text-center cmyk-chip {{ $isPendingDelete ? 'text-muted' : '' }}">{{ $color->yellow }}</td>
                            <td class="text-center cmyk-chip {{ $isPendingDelete ? 'text-muted' : '' }}">{{ $color->black }}</td>
                            {{-- Status badge --}}
                            <td class="text-center">
                                @php
                                    $badgeClass = match($color->status) {
                                        'pending_add'    => 'success',
                                        'pending_edit'   => 'warning text-dark',
                                        'pending_delete' => 'danger',
                                        default          => 'secondary',
                                    };
                                    $badgeIcon = match($color->status) {
                                        'pending_add'    => 'bi-plus-circle',
                                        'pending_edit'   => 'bi-pencil',
                                        'pending_delete' => 'bi-trash3',
                                        default          => 'bi-question',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}" style="font-size:.72rem">
                                    <i class="bi {{ $badgeIcon }} me-1"></i>{{ $color->statusLabel }}
                                </span>
                            </td>
                            <td class="text-center text-muted d-none d-md-table-cell" style="font-size:.8rem">
                                {{ $color->creator->name ?? '—' }}
                            </td>

                            @if(auth()->user()->isPipelineManager())
                                {{-- Proposed new values for edit requests --}}
                                <td class="text-center">
                                    @if($isPendingEdit && $color->pending_hex)
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <span class="color-swatch" style="background:{{ $color->pending_hex }}"></span>
                                            <div class="text-start" style="font-size:.72rem;line-height:1.4">
                                                <div class="fw-semibold">{{ $color->pending_name }}</div>
                                                <div class="text-muted cmyk-chip">
                                                    {{ $color->pending_cyan }}/{{ $color->pending_magenta }}/{{ $color->pending_yellow }}/{{ $color->pending_black }}
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
                                {{-- Approve / Reject buttons --}}
                                <td class="text-end pe-3">
                                    <form method="POST"
                                          action="{{ route('color-codes.approve', $color) }}"
                                          class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success me-1" title="Approve">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('color-codes.reject', $color) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Reject this request?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" title="Reject">
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

{{-- ══════════════════════════════════════════════════════════════
     MODALS
══════════════════════════════════════════════════════════════ --}}

{{-- ── PM: Add Color ──────────────────────────────────────── --}}
@if(auth()->user()->isPipelineManager())
<div class="modal fade" id="addColorModal" tabindex="-1" aria-labelledby="addColorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('color-codes.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addColorModalLabel">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>Add Color
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('color-codes._form', ['prefix' => ''])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Add Color
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── PM: Edit Color ─────────────────────────────────────── --}}
<div class="modal fade" id="editColorModal" tabindex="-1" aria-labelledby="editColorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editColorForm" action="">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editColorModalLabel">
                        <i class="bi bi-pencil me-2 text-warning"></i>Edit Color
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('color-codes._form', ['prefix' => 'edit-'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── Designer: Request Add ──────────────────────────────── --}}
@if(auth()->user()->isDesigner())
<div class="modal fade" id="reqAddColorModal" tabindex="-1" aria-labelledby="reqAddColorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('color-codes.request-store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reqAddColorModalLabel">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>Request New Color
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        This will be sent to the pipeline manager for approval before it appears in the list.
                    </p>
                    @include('color-codes._form', ['prefix' => 'req-'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Submit for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Designer: Request Edit ─────────────────────────────── --}}
<div class="modal fade" id="reqEditColorModal" tabindex="-1" aria-labelledby="reqEditColorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="reqEditColorForm" action="">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="reqEditColorModalLabel">
                        <i class="bi bi-pencil me-2 text-warning"></i>Request Edit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Your proposed changes will be sent to the pipeline manager for approval.
                    </p>
                    @include('color-codes._form', ['prefix' => 'req-edit-'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white">
                        <i class="bi bi-send me-1"></i>Submit for Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// ── Slider ↔ number sync helpers ──────────────────────────
// Called by oninput on the range; copies value to the number input and refreshes preview.
function syncCmyk(fieldId) {
    const range  = document.getElementById(fieldId + '-range');
    const number = document.getElementById(fieldId);
    if (!range || !number) return;
    number.value = range.value;
    refreshPreview(fieldId);
}

// Called by oninput on the number input; copies value to the range and refreshes preview.
function syncCmykRange(fieldId) {
    const range  = document.getElementById(fieldId + '-range');
    const number = document.getElementById(fieldId);
    if (!range || !number) return;
    range.value = number.value;
    refreshPreview(fieldId);
}

// Determine which modal prefix this field belongs to and refresh its swatch.
function refreshPreview(fieldId) {
    // fieldId examples: 'cyan', 'edit-cyan', 'req-cyan', 'req-edit-cyan'
    // Strip the channel suffix to get the prefix.
    const channels = ['cyan','magenta','yellow','black'];
    let prefix = '';
    for (const ch of channels) {
        if (fieldId.endsWith(ch)) {
            prefix = fieldId.slice(0, fieldId.length - ch.length);
            break;
        }
    }
    updateSwatch(prefix);
}

// ── CMYK → hex ────────────────────────────────────────────
function cmykToHex(c, m, y, k) {
    const r = Math.round(255 * (1 - c/100) * (1 - k/100));
    const g = Math.round(255 * (1 - m/100) * (1 - k/100));
    const b = Math.round(255 * (1 - y/100) * (1 - k/100));
    return '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('');
}

function updateSwatch(prefix) {
    const get = id => parseInt(document.getElementById(prefix + id)?.value) || 0;
    const swatch = document.getElementById(prefix + 'preview-swatch');
    if (!swatch) return;
    swatch.style.background = cmykToHex(get('cyan'), get('magenta'), get('yellow'), get('black'));
}

// ── Populate PM edit modal on open ────────────────────────
const editModal = document.getElementById('editColorModal');
if (editModal) {
    editModal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('editColorForm').action = '/color-codes/' + btn.dataset.id;

        document.getElementById('edit-name').value    = btn.dataset.name;

        const fields = ['cyan','magenta','yellow','black'];
        const vals   = [btn.dataset.c, btn.dataset.m, btn.dataset.y, btn.dataset.k];
        fields.forEach((f, i) => {
            const num   = document.getElementById('edit-' + f);
            const range = document.getElementById('edit-' + f + '-range');
            if (num)   num.value   = vals[i];
            if (range) range.value = vals[i];
        });
        updateSwatch('edit-');
    });
}

// ── Populate Designer request-edit modal on open ──────────
const reqEditModal = document.getElementById('reqEditColorModal');
if (reqEditModal) {
    reqEditModal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('reqEditColorForm').action = '/color-codes/' + btn.dataset.id + '/request';

        document.getElementById('req-edit-name').value = btn.dataset.name;

        const fields = ['cyan','magenta','yellow','black'];
        const vals   = [btn.dataset.c, btn.dataset.m, btn.dataset.y, btn.dataset.k];
        fields.forEach((f, i) => {
            const num   = document.getElementById('req-edit-' + f);
            const range = document.getElementById('req-edit-' + f + '-range');
            if (num)   num.value   = vals[i];
            if (range) range.value = vals[i];
        });
        updateSwatch('req-edit-');
    });
}
</script>
@endpush
