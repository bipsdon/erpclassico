@extends('layouts.app')
@section('title', $client->name . ' — Ledger')
@section('page-title')
    <i class="bi bi-receipt me-2 text-primary"></i>{{ $client->name }}
    <small class="text-muted fw-normal ms-2" style="font-size:.8rem">Client Ledger</small>
@endsection

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    }
    .bal-due    { color: #dc3545; font-weight: 600; }   /* client owes us */
    .bal-credit { color: #198754; font-weight: 600; }   /* client is in credit */
    .bal-zero   { color: #6c757d; }
    tr.row-payment td { background: #f0fdf4 !important; }
    tr.row-payment td { font-style: italic; color: #166534; }
    tr.row-payment .credit-amt { font-style: normal; font-weight: 600; color: #166534; }
</style>
@endpush

@section('content')

{{-- ── Back + action bar ───────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-4 no-print">
    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>All Clients
    </a>
    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-pencil me-1"></i>Edit Client
    </a>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="{{ route('clients.export.xlsx', $client) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>XLSX
        </a>
        <a href="{{ route('clients.export.pdf', $client) }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-printer me-1"></i>Print
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show no-print">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show no-print">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── Summary cards ───────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-4">
                <h5 class="fw-bold mb-1">{{ $client->name }}</h5>
                @if($client->phone)
                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $client->phone }}</div>
                @endif
                @if($client->address)
                    <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $client->address }}</div>
                @endif
            </div>
            <div class="col-12 col-md-8">
                <div class="row g-2 text-center">
                    <div class="col-3">
                        <div class="border rounded-2 p-2">
                            <div class="fw-bold text-primary fs-5">{{ $orders->count() }}</div>
                            <div class="text-muted small">Orders</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded-2 p-2">
                            <div class="fw-bold text-dark fs-5">Rs. {{ number_format($totalBilled, 2) }}</div>
                            <div class="text-muted small">Total Billed</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded-2 p-2">
                            <div class="fw-bold text-success fs-5">Rs. {{ number_format($totalPaid, 2) }}</div>
                            <div class="text-muted small">Total Paid</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded-2 p-2">
                            @if($netBalance < 0)
                                <div class="fw-bold bal-credit fs-5">Rs. {{ number_format(abs($netBalance), 2) }}</div>
                                <div class="text-muted small">In Credit</div>
                            @elseif($netBalance == 0)
                                <div class="fw-bold bal-zero fs-5">Rs. 0.00</div>
                                <div class="text-muted small">Settled</div>
                            @else
                                <div class="fw-bold bal-due fs-5">Rs. {{ number_format($netBalance, 2) }}</div>
                                <div class="text-muted small">Outstanding</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
{{-- ── LEFT: Ledger ─────────────────────────────────────── --}}
<div class="col-12 col-xl-8">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-journal-text me-2 text-primary"></i>Account Statement
            <small class="text-muted fw-normal ms-2">chronological — debit/credit running balance</small>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="text-end">Debit (Rs.)</th>
                        <th class="text-end">Credit (Rs.)</th>
                        <th class="text-end">Balance (Rs.)</th>
                        <th class="no-print text-center" style="width:80px">Bill</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        @if($event['type'] === 'order')
                            @php
                                $order  = $event['order'];
                                $bill   = $order->bill;
                                $debit  = $event['debit'];
                                $runBal = $event['running_balance'];
                            @endphp
                            <tr class="row-order">
                                <td class="text-nowrap">{{ $order->order_date->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="text-decoration-none fw-semibold text-dark">
                                        {{ $order->order_number }}
                                    </a>
                                    @if($order->whatsapp_order_id)
                                        <span class="text-muted ms-1" style="font-size:.78rem">
                                            <i class="bi bi-whatsapp text-success"></i> {{ $order->whatsapp_order_id }}
                                        </span>
                                    @endif
                                    <span class="badge ms-1 bg-{{ match($order->stage) {
                                        'delivered' => 'success', 'ready' => 'info',
                                        'design' => 'primary', 'print' => 'warning',
                                        'sew' => 'secondary', default => 'light text-dark'
                                    } }}" style="font-size:.7rem">{{ $order->stage_label }}</span>
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ $debit > 0 ? number_format($debit, 2) : '—' }}
                                </td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end fw-semibold
                                    {{ $runBal < 0 ? 'bal-credit' : ($runBal > 0 ? 'bal-due' : 'bal-zero') }}">
                                    @if($runBal < 0)
                                        ({{ number_format(abs($runBal), 2) }})
                                    @else
                                        {{ number_format($runBal, 2) }}
                                    @endif
                                </td>
                                <td class="no-print text-center">
                                    <button class="btn btn-sm btn-outline-primary py-0"
                                            data-bs-toggle="modal"
                                            data-bs-target="#bill-modal-{{ $order->id }}">
                                        <i class="bi bi-{{ $bill ? 'pencil' : 'plus-circle' }}"></i>
                                    </button>
                                </td>
                            </tr>
                        @else
                            @php
                                $pmt    = $event['payment'];
                                $runBal = $event['running_balance'];
                            @endphp
                            <tr class="row-payment">
                                <td class="text-nowrap">{{ $pmt->payment_date->format('d M Y') }}</td>
                                <td>
                                    <i class="bi bi-cash-coin me-1"></i>Payment received
                                    @if($pmt->method_label !== '—')
                                        <span class="text-muted small">({{ $pmt->method_label }})</span>
                                    @endif
                                    @if($pmt->reference)
                                        <span class="text-muted small">· Ref: {{ $pmt->reference }}</span>
                                    @endif
                                    @if($pmt->notes)
                                        <div class="text-muted" style="font-size:.78rem">{{ $pmt->notes }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end credit-amt">{{ number_format($pmt->amount, 2) }}</td>
                                <td class="text-end fw-semibold
                                    {{ $runBal < 0 ? 'bal-credit' : ($runBal > 0 ? 'bal-due' : 'bal-zero') }}">
                                    @if($runBal < 0)
                                        ({{ number_format(abs($runBal), 2) }})
                                    @else
                                        {{ number_format($runBal, 2) }}
                                    @endif
                                </td>
                                <td class="no-print text-center">
                                    <form method="POST"
                                          action="{{ route('clients.payments.destroy', [$client, $pmt]) }}"
                                          onsubmit="return confirm('Delete this payment record?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No transactions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($events->count() > 0)
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td colspan="2" class="text-end">Totals</td>
                        <td class="text-end">{{ number_format($totalBilled, 2) }}</td>
                        <td class="text-end text-success">{{ number_format($totalPaid, 2) }}</td>
                        <td class="text-end {{ $netBalance < 0 ? 'bal-credit' : ($netBalance > 0 ? 'bal-due' : 'bal-zero') }}">
                            @if($netBalance < 0)
                                ({{ number_format(abs($netBalance), 2) }}) <small class="fw-normal text-muted">credit</small>
                            @else
                                {{ number_format($netBalance, 2) }}
                            @endif
                        </td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Legend --}}
    <div class="d-flex gap-3 mb-3 text-muted no-print" style="font-size:.8rem">
        <span><span class="badge bg-light text-dark border me-1">White rows</span> Orders (debit)</span>
        <span><span class="badge me-1" style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0">Green rows</span> Payments (credit)</span>
        <span class="ms-2"><strong>(amount)</strong> = negative balance / client in credit</span>
    </div>

</div>

{{-- ── RIGHT: Record payment ───────────────────────────── --}}
<div class="col-12 col-xl-4 no-print">
    <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-cash-coin me-2 text-success"></i>Record Payment
        </div>
        <div class="card-body">

            @if($netBalance < 0)
                <div class="alert alert-success py-2 mb-3">
                    <i class="bi bi-piggy-bank me-1"></i>
                    Client is in <strong>credit by Rs. {{ number_format(abs($netBalance), 2) }}</strong>.
                    Any new orders will draw from this credit first.
                </div>
            @elseif($netBalance == 0 && $events->count() > 0)
                <div class="alert alert-success py-2 mb-3">
                    <i class="bi bi-check-circle me-1"></i>Account is fully settled.
                </div>
            @else
                <div class="text-muted small mb-3">
                    <strong>Outstanding:</strong>
                    <span class="bal-due">Rs. {{ number_format($netBalance, 2) }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('clients.payments.store', $client) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Amount Received <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">Rs.</span>
                        <input type="number" name="amount" step="0.01" min="0.01"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" placeholder="0.00" required>
                    </div>
                    @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date"
                           class="form-control @error('payment_date') is-invalid @enderror"
                           value="{{ old('payment_date', now()->toDateString()) }}" required>
                    @error('payment_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Payment Method</label>
                    <select name="method" class="form-select">
                        <option value="">— Select —</option>
                        <option value="cash"          {{ old('method') == 'cash'          ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ old('method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="esewa"         {{ old('method') == 'esewa'         ? 'selected' : '' }}>eSewa</option>
                        <option value="khalti"        {{ old('method') == 'khalti'        ? 'selected' : '' }}>Khalti</option>
                        <option value="cheque"        {{ old('method') == 'cheque'        ? 'selected' : '' }}>Cheque</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reference / Txn ID</label>
                    <input type="text" name="reference" class="form-control"
                           value="{{ old('reference') }}" placeholder="Cheque no., txn ID, etc.">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" rows="2" class="form-control"
                              placeholder="Optional remarks">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check2-circle me-1"></i>Record Payment
                </button>
            </form>
        </div>
    </div>
</div>

</div>{{-- end row --}}

{{-- ── Bill modals ─────────────────────────────────────── --}}
@foreach($orders as $order)
<div class="modal fade no-print" id="bill-modal-{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('orders.bill.upsert', $order) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">
                        <i class="bi bi-receipt me-2"></i>Bill — {{ $order->order_number }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php $b = $order->bill; @endphp
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        These fields are for the <strong>per-order bill PDF</strong> only.
                        Client-level balance is tracked via the payments panel.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="amount" step="0.01" min="0"
                                   class="form-control"
                                   value="{{ old('amount', $b?->amount ?? '') }}"
                                   placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount Received on this order</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="received" step="0.01" min="0"
                                   class="form-control"
                                   value="{{ old('received', $b?->received ?? '0') }}"
                                   placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"
                                  placeholder="e.g. 50% advance">{{ old('notes', $b?->notes ?? '') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ route('orders.bill.pdf', $order) }}" class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Download Bill
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Save Bill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection
