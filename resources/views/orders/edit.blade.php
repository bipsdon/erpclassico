@extends('layouts.app')

@section('title', 'Edit ' . $order->order_number)
@section('page-title')
    <i class="bi bi-pencil me-2 text-primary"></i>Edit {{ $order->order_number }}
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Order
    </a>
    <span class="badge bg-secondary ms-2">{{ $order->order_number }}</span>
    <span class="badge bg-{{ $order->priority_badge }}">{{ ucfirst($order->priority) }}</span>
</div>

{{--
    IMPORTANT: The delete form must stay OUTSIDE this form.
    Nested HTML forms are invalid — browsers ignore the inner form
    and both buttons submit to whichever action the outer form points to.
    The delete button below uses a separate standalone form rendered after
    this closing </form> tag, triggered via JS.
--}}
<form method="POST"
      action="{{ route('orders.update', $order) }}"
      enctype="multipart/form-data"
      id="order-form">
    @csrf
    @method('PUT')

    <div class="row g-4">

        {{-- Left: all form fields --}}
        <div class="col-12 col-xl-9">
            @include('orders._form', ['order' => $order])
        </div>

        {{-- Right: sticky action panel --}}
        <div class="col-12 col-xl-3">
            <div class="card border-0 shadow-sm" style="position:sticky;top:72px">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Save Changes</h6>

                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check2-circle me-2"></i>Update Order
                    </button>

                    <a href="{{ route('orders.show', $order) }}"
                       class="btn btn-outline-secondary w-100 mb-3">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>

                    <hr class="my-2">

                    {{--
                        Delete button submits the SEPARATE delete-form below
                        via JS — it is NOT inside the update form.
                    --}}
                    <button type="button"
                            class="btn btn-outline-danger w-100"
                            onclick="confirmDelete()">
                        <i class="bi bi-trash me-1"></i>Delete Order
                    </button>

                    <hr class="my-3">

                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Changing delivery date or priority triggers a schedule recalculation.
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>{{-- ← update form ends here --}}

{{-- Standalone delete form — lives OUTSIDE the update form --}}
<form method="POST"
      action="{{ route('orders.destroy', $order) }}"
      id="delete-order-form">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    @include('orders._form_scripts')
    <script>
        function confirmDelete() {
            if (confirm('Permanently delete {{ $order->order_number }}? This cannot be undone.')) {
                document.getElementById('delete-order-form').submit();
            }
        }
    </script>
@endpush
