@extends('layouts.app')
@section('title', 'Custom Bills')
@section('page-title')
    <i class="bi bi-receipt-cutoff me-2 text-success"></i>Custom Bills
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">All saved custom bills, newest first.</p>
    <a href="{{ route('billing.custom.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i>New Bill
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Invoice No.</th>
                    <th>Bill To</th>
                    <th>Date</th>
                    <th class="text-end">Total</th>
                    <th>Created By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                    <tr>
                        <td>
                            <span class="fw-semibold text-success">{{ $bill->invoice_number }}</span>
                        </td>
                        <td>{{ $bill->bill_to }}</td>
                        <td>{{ $bill->bill_date->format('d M Y') }}</td>
                        <td class="text-end fw-semibold">Rs. {{ number_format($bill->subtotal, 2) }}</td>
                        <td class="text-muted small">{{ $bill->creator?->name ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('billing.custom.download', $bill) }}"
                               class="btn btn-sm btn-outline-danger me-1"
                               title="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                            <form method="POST"
                                  action="{{ route('billing.custom.destroy', $bill) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete bill {{ $bill->invoice_number }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            No bills yet. <a href="{{ route('billing.custom.create') }}">Create the first one.</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($bills->hasPages())
    <div class="mt-3">{{ $bills->links() }}</div>
@endif

@endsection
