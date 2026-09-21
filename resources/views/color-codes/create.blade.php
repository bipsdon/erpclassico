@extends('layouts.app')

@section('title', 'Add Color')
@section('page-title')
    <i class="bi bi-palette me-2 text-primary"></i>Add Color
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">

        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('color-codes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <h5 class="mb-0 fw-semibold">New Color Code</h5>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('color-codes.store') }}">
                    @csrf

                    @include('color-codes._form')

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('color-codes.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Add Color
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
