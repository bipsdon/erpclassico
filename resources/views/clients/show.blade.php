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
    .balance-positive { color: #dc3545; font-weight: 600; }
    .balance-zero     { color: #198754; font-weight: 600; }
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
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download XLSX
        </a>
        <a href="{{ route('clients.export.pdf', $client) }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
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

{{-- ── Client info + summary ───────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
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
                            <div class="fw-bold text-success fs-5">Rs. {{ number_format($totalReceived, 2) }}</div>
                            <div class="text-muted small">Total Received</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="border rounded-2 p-2">
                            <div class="fw-bold {{ $totalBalance > 0 ? 'balance-positive' : 'balance-zero' }} fs-5">
                                Rs. {{ number_format($totalBalance, 2) }}
                            </div>
                            <div class="text-muted small">Outstanding</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
{{-- ── LEFT: Ledger + Payment history ─────────────────── --}}
<div class="col-12 col-xl-8">

    {{-- Ledger table --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>Order Ledger
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Order Date</th>
                        <th>Delivered</th>
                        <th>Stage</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Balance</th>
                        <th class="no-print text-center">Bill</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $bill         = $order->bill;
                            $amount       = $bill?->amount   ?? 0;
                            $received     = $bill?->received ?? 0;
                            $balance      = $amount - $received;
                            $deliveredLog = $order->stageLogs->first(fn($l) => $l->to_stage === 'delivered');
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="text-decoration-none fw-semibold">
                                    {{ $order->order_number }}
                                </a>
                                @if($order->whatsapp_order_id)
                                    <div class="text-muted" style="font-size:.78rem">
                                        <i class="bi bi-whatsapp text-success me-1"></i>{{ $order->whatsapp_order_id }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $order->order_date->format('d M Y') }}</td>
                            <td>
                                {{ $deliveredLog ? $deliveredLog->created_at->format('d M Y') : '—' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ match($order->stage) {
                                    'delivered' => 'success',
                                    'ready'     => 'info',
                                    'design'    => 'primary',
                                    'print'     => 'warning',
                                    'sew'       => 'secondary',
                                    default     => 'light text-dark',
                                } }}">{{ $order->stage_label }}</span>
                            </td>
                            <td class="text-end">{{ $amount > 0 ? 'Rs. '.number_format($amount, 2) : '—' }}</td>
                            <td class="text-end text-success">{{ $received > 0 ? 'Rs. '.number_format($received, 2) : '—' }}</td>
                            <td class="text-end {{ $balance > 0 ? 'balance-positive' : ($balance == 0 && $amount > 0 ? 'balance-zero' : 'text-muted') }}">
                                {{ $amount > 0 ? 'Rs. '.number_format($balance, 2) : '—' }}
                            </td>
                            <td class="no-print text-center">
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#bill-modal-{{ $order->id }}">
                                    <i class="bi bi-{{ $bill ? 'pencil' : 'plus-circle' }}"></i>
                                    {{ $bill ? 'Edit' : 'Add' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                No orders linked to this client yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($orders->count() > 0)
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td colspan="4" class="text-end">Totals</td>
                        <td class="text-end">Rs. {{ number_format($totalBilled, 2) }}</td>
                        <td class="text-end text-success">Rs. {{ number_format($totalReceived, 2) }}</td>
                        <td class="text-end {{ $totalBalance > 0 ? 'balance-positive' : 'balance-zero' }}">
                            Rs. {{ number_format($totalBalance, 2) }}
                        </td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Payment history --}}
    @if($payments->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-clock-history me-2 text-success"></i>Payment History
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Notes</th>
                        <th>Recorded By</th>
                        <th class="no-print"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $pmt)
                    <tr>
                        <td>{{ $pmt->payment_date->format('d M Y') }}</td>
                        <td class="fw-semibold text-success">Rs. {{ number_format($pmt->amount, 2) }}</td>
                        <td>{{ $pmt->method_label }}</td>
                        <td>{{ $pmt->reference ?: '—' }}</td>
                        <td>{{ $pmt->notes ?: '—' }}</td>
                        <td class="text-muted small">{{ $pmt->recorder?->name ?? '—' }}</td>
                        <td class="no-print">
                            <form method="POST"
                                  action="{{ route('clients.payments.destroy', [$client, $pmt]) }}"
                                  onsubmit="return confirm('Delete this payment record? This will NOT reverse any amounts already applied to bills.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td>Total Payments</td>
                        <td class="text-success">Rs. {{ number_format($totalPaid, 2) }}</td>
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- ── RIGHT: Record payment ───────────────────────────── --}}
<div class="col-12 col-xl-4 no-print">
    <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
        <div class="card-header bg-white py-3 fw-semibold">
            <i class="bi bi-cash-coin me-2 text-success"></i>Record Payment
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Enter the total amount received. It will be automatically applied to outstanding orders,
                oldest first, until the payment is used up.
            </p>

            @if($totalBalance <= 0 && $orders->count() > 0)
                <div class="alert alert-success py-2 mb-3">
                    <i class="bi bi-check-circle me-1"></i>All orders are fully paid.
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
                               value="{{ old('amount') }}"
                               placeholder="0.00" required>
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
                    <select name="method" class="form-select @error('method') is-invalid @enderror">
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
                    <input type="text" name="reference"
                           class="form-control @error('reference') is-invalid @enderror"
                           value="{{ old('reference') }}"
                           placeholder="Cheque no., txn ID, etc.">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" rows="2"
                              class="form-control @error('notes') is-invalid @enderror"
                              placeholder="Optional remarks">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check2-circle me-1"></i>Record Payment
                </button>
            </form>

            @if($totalBalance > 0)
            <hr class="my-3">
            <div class="text-muted small">
                <strong>Outstanding balance:</strong>
                <span class="text-danger fw-semibold">Rs. {{ number_format($totalBalance, 2) }}</span>
                across {{ $orders->where(fn($o) => ($o->bill?->amount ?? 0) - ($o->bill?->received ?? 0) > 0)->count() }}
                order(s).
            </div>
            @endif
        </div>
    </div>
</div>

</div>{{-- end row --}}

{{-- ── Bill modals (one per order) ────────────────────────── --}}
@foreach($orders as $order)
<div class="modal fade no-print" id="bill-modal-{{ $order->id }}" tabindex="-1"
     aria-labelledby="bill-modal-label-{{ $order->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('orders.bill.upsert', $order) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="bill-modal-label-{{ $order->id }}">
                        <i class="bi bi-receipt me-2"></i>Bill — {{ $order->order_number }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php $b = $order->bill; @endphp
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
                        <label class="form-label fw-semibold">Amount Received <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="received" step="0.01" min="0"
                                   class="form-control"
                                   value="{{ old('received', $b?->received ?? '0') }}"
                                   placeholder="0.00" required>
                        </div>
                        <div class="form-text">Balance is calculated automatically.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"
                                  placeholder="e.g. 50% advance, rest on delivery">{{ old('notes', $b?->notes ?? '') }}</textarea>
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
