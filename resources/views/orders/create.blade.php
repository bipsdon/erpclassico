@extends('layouts.app')

@section('title', 'New Order')
@section('page-title')
    <i class="bi bi-plus-circle me-2 text-success"></i>New Order
@endsection

@push('styles')
    {{-- Quill Rich Text Editor --}}
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>All Orders
    </a>
</div>

<form method="POST"
      action="{{ route('orders.store') }}"
      enctype="multipart/form-data"
      id="order-form">
    @csrf

    <div class="row g-4">
        <div class="col-12 col-xl-9">
            @include('orders._form')
        </div>

        <div class="col-12 col-xl-3">
            {{-- Sticky submit panel --}}
            <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Save Order</h6>
                    <button type="submit" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check2-circle me-2"></i>Create Order
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>
                    <hr class="my-3">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Order will be automatically added to the design queue and the schedule will be recalculated.
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    @include('orders._form_scripts')
@endpush
