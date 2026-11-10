@extends('layouts.app')

@section('title', 'Bulk Import Orders')
@section('page-title')
    <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Bulk Import Orders
@endsection

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>All Orders
    </a>
</div>

<div class="row g-4">
    {{-- ── Upload card ─────────────────────────────── --}}
    <div class="col-12 col-xl-8">

        {{-- Success / warning / error flashes --}}
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                {{ session('warning') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center gap-2">
                <i class="bi bi-x-circle-fill flex-shrink-0"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- Row-level import errors --}}
        @if(session('import_errors'))
            <div class="card border-danger border-opacity-50 shadow-sm mb-4">
                <div class="card-header bg-danger bg-opacity-10 py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <span class="fw-semibold text-danger">
                        {{ count(session('import_errors')) }} row(s) had validation errors
                    </span>
                    <button class="btn btn-sm btn-link text-danger ms-auto p-0"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#import-errors-list">
                        Toggle
                    </button>
                </div>
                <div class="collapse show" id="import-errors-list">
                    <ul class="list-group list-group-flush" style="font-size:.82rem">
                        @foreach(session('import_errors') as $err)
                            <li class="list-group-item text-danger py-1 px-3">
                                <i class="bi bi-dot me-1"></i>{{ $err }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-upload me-2 text-primary"></i>Upload Spreadsheet
            </div>
            <div class="card-body">

                <form method="POST"
                      action="{{ route('orders.import.store') }}"
                      enctype="multipart/form-data"
                      id="import-form">
                    @csrf

                    {{-- Drop zone --}}
                    <div id="drop-zone"
                         class="border border-2 border-dashed rounded-3 text-center p-5 mb-3"
                         style="cursor:pointer;transition:background .2s;border-color:#dee2e6!important">
                        <i class="bi bi-file-earmark-spreadsheet display-5 text-primary mb-3 d-block"></i>
                        <div class="fw-semibold mb-1">Drop your file here, or click to browse</div>
                        <div class="text-muted small">XLSX · XLS · CSV &nbsp;·&nbsp; max 10 MB</div>
                        <div id="drop-zone-filename" class="mt-3 text-success fw-semibold small" style="display:none"></div>
                        <input type="file"
                               name="import_file"
                               id="import_file"
                               accept=".xlsx,.xls,.csv"
                               class="d-none"
                               required>
                    </div>

                    @error('import_file')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" id="import-submit-btn" disabled>
                            <i class="bi bi-cloud-upload me-2"></i>Import Orders
                        </button>
                        <a href="{{ route('orders.import.template') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-download me-2"></i>Download Template
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- ── Instructions sidebar ────────────────────── --}}
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-info-circle me-2 text-primary"></i>How it works
            </div>
            <div class="card-body" style="font-size:.85rem">

                <ol class="ps-3 mb-4">
                    <li class="mb-2">
                        <strong>Download the template</strong> — it has the correct columns and an example row already filled in.
                    </li>
                    <li class="mb-2">
                        Fill in your orders. Each row = one order. Add as many rows as you need.
                    </li>
                    <li class="mb-2">
                        For player lists, use the <code>player_1_name</code>, <code>player_1_number</code>… columns (increment the number for each player).
                    </li>
                    <li class="mb-2">
                        Save and upload here. Valid rows are created instantly; invalid rows are skipped with an error message.
                    </li>
                </ol>

                <hr class="my-3">

                <div class="fw-semibold mb-2">Required columns</div>
                <table class="table table-sm table-bordered" style="font-size:.8rem">
                    <tbody>
                        <tr><td><code>customer_name</code></td><td>Text</td></tr>
                        <tr><td><code>customer_phone</code></td><td>Text</td></tr>
                        <tr><td><code>quantity</code></td><td>Number ≥ 1</td></tr>
                        <tr><td><code>product_type</code></td><td><code>jersey</code> · <code>tracksuit</code> · <code>polo_shirt</code> · <code>shorts</code> · <code>other</code></td></tr>
                        <tr><td><code>order_date</code></td><td><code>YYYY-MM-DD</code></td></tr>
                        <tr><td><code>delivery_date</code></td><td><code>YYYY-MM-DD</code> ≥ order date</td></tr>
                    </tbody>
                </table>

                <div class="fw-semibold mb-2 mt-3">Optional columns</div>
                <table class="table table-sm table-bordered" style="font-size:.8rem">
                    <tbody>
                        <tr><td><code>priority</code></td><td><code>normal</code> · <code>rush</code> · <code>critical</code></td></tr>
                        <tr><td><code>pipeline</code></td><td><code>design,print,sew</code></td></tr>
                        <tr><td><code>whatsapp_order_id</code></td><td>Text</td></tr>
                        <tr><td><code>notes</code></td><td>Text</td></tr>
                        <tr><td><code>player_N_name</code></td><td>Player name (N = 1,2,3…)</td></tr>
                        <tr><td><code>player_N_number</code></td><td>Jersey number</td></tr>
                        <tr><td><code>player_N_size</code></td><td>XS S M L XL XXL 3XL</td></tr>
                        <tr><td><code>player_N_notes</code></td><td>Player notes</td></tr>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const dropZone  = document.getElementById('drop-zone');
    const fileInput = document.getElementById('import_file');
    const filename  = document.getElementById('drop-zone-filename');
    const submitBtn = document.getElementById('import-submit-btn');

    function applyFile(file) {
        if (!file) return;

        // Validate extension client-side
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xlsx','xls','csv'].includes(ext)) {
            filename.textContent = 'Invalid file type. Please use XLSX, XLS, or CSV.';
            filename.style.color  = '#dc3545';
            filename.style.display = '';
            submitBtn.disabled = true;
            return;
        }

        filename.textContent = `✓ ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        filename.style.color  = '#198754';
        filename.style.display = '';
        submitBtn.disabled = false;
        dropZone.style.background = '#f0fff4';
    }

    // Click to browse
    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function () {
        applyFile(this.files[0] ?? null);
    });

    // Drag & drop
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.style.background = '#e8f4fd';
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.style.background = '';
    });

    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.style.background = '';
        const file = e.dataTransfer.files[0];
        if (file) {
            // Transfer to the real input so the form sends it
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            applyFile(file);
        }
    });

    // Loading state on submit
    document.getElementById('import-form').addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing…';
    });
}());
</script>
@endpush
