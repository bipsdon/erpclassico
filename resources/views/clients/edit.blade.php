@extends('layouts.app')
@section('title', 'Edit Client')
@section('page-title')
    <i class="bi bi-pencil me-2 text-warning"></i>Edit Client
@endsection

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Ledger
    </a>
</div>

<form method="POST" action="{{ route('clients.update', $client) }}">
    @csrf @method('PUT')
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            @include('clients._form')
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Save Changes</h6>
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check2-circle me-2"></i>Update Client
                    </button>
                    <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
