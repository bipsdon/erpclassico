@extends('layouts.app')

@section('title', 'Request Edit')
@section('page-title')
    <i class="bi bi-pencil me-2 text-warning"></i>Request Edit
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">

        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('color-codes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <h5 class="mb-0 fw-semibold">Request Edit: {{ $colorCode->name }}</h5>
        </div>

        <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
            <div>Your proposed changes will be sent to the pipeline manager for approval. The current color stays active until approved.</div>
        </div>

        {{-- Current color for reference --}}
        <div class="card border-0 bg-light mb-4">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <span style="
                    display:inline-block;
                    width:52px;height:36px;
                    border-radius:6px;
                    border:1px solid rgba(0,0,0,.15);
                    background:{{ $colorCode->hex }};
                    flex-shrink:0">
                </span>
                <div>
                    <div class="fw-semibold" style="font-size:.9rem">Current: {{ $colorCode->name }}</div>
                    <div class="text-muted" style="font-size:.78rem;font-family:monospace">
                        C:{{ $colorCode->cyan }} M:{{ $colorCode->magenta }} Y:{{ $colorCode->yellow }} K:{{ $colorCode->black }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('color-codes.request-update', $colorCode) }}">
                    @csrf @method('PUT')

                    @include('color-codes._form', ['colorCode' => $colorCode])

                    <hr class="my-4">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('color-codes.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning text-white">
                            <i class="bi bi-send me-1"></i>Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
