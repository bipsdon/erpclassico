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
    <div class="ms-auto d-flex gap-2">
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

{{-- ── Client info card ────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-5">
                <h5 class="fw-bold mb-1">{{ $client->name }}</h5>
                @if($client->phone)
                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $client->phone }}</div>
                @endif
                @if($client->address)
                    <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $client->address }}</div>
                @endif
            </div>
            <div class="col-12 col-md-7">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="border rounded-2 p-2">
                            <div class="fw-bold text-primary fs-5">{{ $orders->count() }}</div>
                            <div class="text-muted small">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded-2 p-2">
                            <div class="fw-bold text-dark fs-5">{{ number_format($totalBilled, 2) }}</div>
                            <div class="text-muted small">Total Billed</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded-2 p-2">
                            <div class="fw-bold {{ $totalBalance > 0 ? 'balance-positive' : 'balance-zero' }} fs-5">
                                {{ number_format($totalBalance, 2) }}
                            </div>
                            <div class="text-muted small">Outstanding</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Ledger table ─────────────────────────────────────── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2 text-primary"></i>Order Ledger</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>WhatsApp Order ID</th>
                    <th>Order Date</th>
                    <th>Delivered Date</th>
                    <th>Stage</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Balance</th>
                    <th class="no-print text-center">Bill</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBalance = 0; @endphp
                @forelse($orders as $order)
                    @php
                        $bill     = $order->bill;
                        $amount   = $bill ? $bill->amount   : 0;
                        $received = $bill ? $bill->received : 0;
                        $balance  = $amount - $received;
                        $runningBalance += $balance;

                        // Delivered date: from stage log
                        $deliveredLog = $order->stageLogs->first(fn($l) => $l->to_stage === 'delivered');
                        $deliveredDate = $deliveredLog?->created_at;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="text-decoration-none fw-semibold">
                                {{ $order->order_number }}
                            </a>
                            @if($order->whatsapp_order_id)
                                <div class="text-muted small">
                                    <i class="bi bi-whatsapp text-success me-1"></i>{{ $order->whatsapp_order_id }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $order->order_date->format('d M Y') }}</td>
                        <td>
                            @if($deliveredDate)
                                {{ $deliveredDate->format('d M Y') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
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
                        <td class="text-end">{{ $amount > 0 ? number_format($amount, 2) : '—' }}</td>
                        <td class="text-end text-success">{{ $received > 0 ? number_format($received, 2) : '—' }}</td>
                        <td class="text-end {{ $balance > 0 ? 'balance-positive' : ($balance == 0 && $amount > 0 ? 'balance-zero' : 'text-muted') }}">
                            {{ $amount > 0 ? number_format($balance, 2) : '—' }}
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
                        <td colspan="8" class="text-center text-muted py-5">No orders linked to this client yet.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($orders->count() > 0)
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="4" class="text-end">Totals</td>
                    <td class="text-end">{{ number_format($totalBilled, 2) }}</td>
                    <td class="text-end text-success">{{ number_format($totalReceived, 2) }}</td>
                    <td class="text-end {{ $totalBalance > 0 ? 'balance-positive' : 'balance-zero' }}">
                        {{ number_format($totalBalance, 2) }}
                    </td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

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
                                   value="{{ old('amount', $b ? $b->amount : '') }}"
                                   placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount Received <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="received" step="0.01" min="0"
                                   class="form-control"
                                   value="{{ old('received', $b ? $b->received : '0') }}"
                                   placeholder="0.00" required>
                        </div>
                        <div class="form-text">Balance will be calculated automatically.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"
                                  placeholder="e.g. 50% advance, rest on delivery">{{ old('notes', $b ? $b->notes : '') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ route('orders.bill.pdf', $order) }}"
                       class="btn btn-outline-danger"
                       title="Download Bill PDF">
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

@push('scripts')
<script>
// Auto-open bill modal if there was a validation error on a specific order
// (not strictly needed here since we redirect back, but useful if wired up)
</script>
@endpush
