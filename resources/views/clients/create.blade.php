@extends('layouts.app')
@section('title', 'New Client')
@section('page-title')
    <i class="bi bi-person-plus me-2 text-success"></i>New Client
@endsection

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>All Clients
    </a>
</div>

<form method="POST" action="{{ route('clients.store') }}">
    @csrf
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            @include('clients._form')
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Save Client</h6>
                    <button type="submit" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check2-circle me-2"></i>Create Client
                    </button>
                    <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
