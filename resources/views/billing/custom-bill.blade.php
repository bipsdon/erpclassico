@extends('layouts.app')
@section('title', 'New Custom Bill')
@section('page-title')
    <i class="bi bi-receipt-cutoff me-2 text-success"></i>New Bill
@endsection

@push('styles')
<style>
    .item-row td { vertical-align: middle; }
    .row-total { font-weight: 600; color: #1e3a5f; }
    #grand-total { font-size: 1.25rem; font-weight: 700; color: #1e3a5f; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('billing.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>All Bills
    </a>
</div>

<form method="POST" action="{{ route('billing.custom.store') }}" id="bill-form">
@csrf

<div class="row g-4">

{{-- ── LEFT: Bill builder ───────────────────────────────── --}}
<div class="col-12 col-xl-8">

    {{-- Bill details --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-info-circle me-2 text-primary"></i>Bill Details
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Bill To <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="bill_to"
                           class="form-control @error('bill_to') is-invalid @enderror"
                           value="{{ old('bill_to') }}"
                           placeholder="Customer / organisation name"
                           required>
                    @error('bill_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Bill Date <span class="text-danger">*</span></label>
                    <input type="date" name="bill_date"
                           class="form-control @error('bill_date') is-invalid @enderror"
                           value="{{ old('bill_date', now()->toDateString()) }}"
                           required>
                    @error('bill_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Invoice No.</label>
                    <input type="text" class="form-control bg-light text-muted"
                           value="{{ $nextNumber }}" readonly
                           title="Auto-generated on save">
                    <div class="form-text">Auto-generated on save.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Line items --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check me-2 text-primary"></i>Line Items</span>
            <button type="button" class="btn btn-sm btn-outline-success" id="add-row">
                <i class="bi bi-plus-circle me-1"></i>Add Item
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="items-table">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:200px">Product / Service</th>
                        <th style="min-width:150px">Description</th>
                        <th style="width:85px">Qty</th>
                        <th style="width:130px">Unit Price (Rs.)</th>
                        <th style="width:130px" class="text-end">Total (Rs.)</th>
                        <th style="width:44px"></th>
                    </tr>
                </thead>
                <tbody id="items-body"></tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-end align-items-center gap-4">
                <span class="text-muted fw-semibold">Grand Total</span>
                <span id="grand-total">Rs. 0.00</span>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-sticky me-2 text-warning"></i>Notes
            <small class="text-muted fw-normal">(optional)</small>
        </div>
        <div class="card-body">
            <textarea name="notes" rows="3" class="form-control"
                      placeholder="e.g. Payment due within 15 days. Thank you for your business.">{{ old('notes') }}</textarea>
        </div>
    </div>

</div>

{{-- ── RIGHT: Preview & save ───────────────────────────── --}}
<div class="col-12 col-xl-4">
    <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Generate Bill
        </div>
        <div class="card-body">

            {{-- Live mini-preview --}}
            <div class="border rounded-2 p-3 mb-3 bg-light" style="font-size:.82rem">
                <div class="fw-bold text-primary mb-0" style="font-size:.88rem">Classico Sports Studio Pvt. Ltd.</div>
                <div class="text-muted" style="font-size:.73rem">Satungal Complex, Satungal, Kathmandu</div>
                <hr class="my-2">
                <div><span class="text-muted">Invoice No.:</span> <span class="fw-semibold text-success">{{ $nextNumber }}</span></div>
                <div><span class="text-muted">Bill To:</span> <span id="preview-bill-to" class="fw-semibold">—</span></div>
                <div><span class="text-muted">Date:</span> <span id="preview-date">{{ now()->format('d M Y') }}</span></div>
                <div><span class="text-muted">Items:</span> <span id="preview-items">0</span></div>
                <div class="mt-2 fw-bold" style="color:#1e3a5f">
                    Total: <span id="preview-total">Rs. 0.00</span>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 mb-3">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <button type="submit" class="btn btn-danger w-100 mb-2">
                <i class="bi bi-file-earmark-pdf me-2"></i>Save &amp; Download PDF
            </button>
            <div class="text-muted small text-center">
                <i class="bi bi-info-circle me-1"></i>Bill is saved and PDF downloads immediately.
            </div>
        </div>
    </div>
</div>

</div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const tbody     = document.getElementById('items-body');
    const addBtn    = document.getElementById('add-row');
    const totalEl   = document.getElementById('grand-total');
    const prevTotal = document.getElementById('preview-total');
    const prevItems = document.getElementById('preview-items');
    const prevBillTo= document.getElementById('preview-bill-to');
    const prevDate  = document.getElementById('preview-date');
    const billToIn  = document.querySelector('[name="bill_to"]');
    const dateIn    = document.querySelector('[name="bill_date"]');

    let rowIndex = 0;

    function fmt(n) {
        return 'Rs. ' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function addRow(title, description, qty, price) {
        const i  = rowIndex++;
        const tr = document.createElement('tr');
        tr.className  = 'item-row';
        tr.dataset.idx = i;
        tr.innerHTML = `
            <td>
                <input type="text" name="items[${i}][title]"
                       class="form-control form-control-sm"
                       value="${escAttr(title  || '')}"
                       placeholder="e.g. Jersey printing" required>
            </td>
            <td>
                <input type="text" name="items[${i}][description]"
                       class="form-control form-control-sm"
                       value="${escAttr(description || '')}"
                       placeholder="Optional detail">
            </td>
            <td>
                <input type="number" name="items[${i}][qty]"
                       class="form-control form-control-sm qty-input"
                       value="${qty !== undefined ? qty : 1}" min="0" step="any" required>
            </td>
            <td>
                <input type="number" name="items[${i}][price]"
                       class="form-control form-control-sm price-input"
                       value="${price !== undefined ? price : ''}"
                       min="0" step="any" placeholder="0.00" required>
            </td>
            <td class="text-end row-total">—</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
        recalcRow(tr);
    }

    function recalcRow(tr) {
        const qty   = parseFloat(tr.querySelector('.qty-input').value)   || 0;
        const price = parseFloat(tr.querySelector('.price-input').value) || 0;
        tr.querySelector('.row-total').textContent = qty * price > 0 ? fmt(qty * price) : '—';
    }

    function recalcAll() {
        let grand = 0;
        tbody.querySelectorAll('.item-row').forEach(tr => { recalcRow(tr); grand += rowTotal(tr); });
        const f = fmt(grand);
        totalEl.textContent   = f;
        prevTotal.textContent = f;
        prevItems.textContent = tbody.querySelectorAll('.item-row').length;
    }

    function rowTotal(tr) {
        return (parseFloat(tr.querySelector('.qty-input').value)   || 0) *
               (parseFloat(tr.querySelector('.price-input').value) || 0);
    }

    function escAttr(s) {
        return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    addBtn.addEventListener('click', () => addRow());

    tbody.addEventListener('input', function (e) {
        const tr = e.target.closest('.item-row');
        if (tr) recalcAll();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (!btn) return;
        if (tbody.querySelectorAll('.item-row').length <= 1) {
            alert('At least one item is required.'); return;
        }
        btn.closest('tr').remove();
        recalcAll();
    });

    billToIn.addEventListener('input', () => {
        prevBillTo.textContent = billToIn.value.trim() || '—';
    });

    dateIn.addEventListener('input', () => {
        const d = new Date(dateIn.value);
        if (!isNaN(d))
            prevDate.textContent = d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
    });

    // Seed rows — restore old() on validation failure
    @if(old('items'))
        @foreach(old('items') as $item)
            addRow(
                {{ json_encode($item['title']       ?? '') }},
                {{ json_encode($item['description'] ?? '') }},
                {{ json_encode($item['qty']         ?? 1)  }},
                {{ json_encode($item['price']       ?? '')  }}
            );
        @endforeach
    @else
        addRow();
    @endif

    recalcAll();
    prevBillTo.textContent = billToIn.value.trim() || '—';
}());
</script>
@endpush
