@extends('layouts.app')

@section('title', 'Request New Color')
@section('page-title')
    <i class="bi bi-palette me-2 text-primary"></i>Request New Color
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">

        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('color-codes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <h5 class="mb-0 fw-semibold">Request New Color Code</h5>
        </div>

        <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
            <div>Your submission will be reviewed by the pipeline manager before it appears in the active list.</div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('color-codes.request-store') }}">
                    @csrf

                    @include('color-codes._form')

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('color-codes.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
