@extends('layouts.app')
@section('title', 'Clients')
@section('page-title')
    <i class="bi bi-person-lines-fill me-2 text-primary"></i>Clients
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Regular clients. Selecting one in an order form auto-fills their name and phone.</p>
    <a href="{{ route('clients.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i>New Client
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th class="text-center">Orders</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="fw-semibold text-decoration-none">
                                {{ $client->name }}
                            </a>
                            @if($client->address)
                                <div class="text-muted small">{{ Str::limit($client->address, 60) }}</div>
                            @endif
                        </td>
                        <td>{{ $client->phone ?: '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $client->orders_count }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-receipt me-1"></i>Ledger
                            </a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('clients.destroy', $client) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete client {{ addslashes($client->name) }}? This will not delete their orders.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            No clients yet. <a href="{{ route('clients.create') }}">Add the first one.</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($clients->hasPages())
    <div class="mt-3">{{ $clients->links() }}</div>
@endif

@endsection
