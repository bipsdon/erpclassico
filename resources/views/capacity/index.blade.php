@extends('layouts.app')

@section('title', 'Capacity Settings')
@section('page-title')
    <i class="bi bi-sliders me-2 text-primary"></i>Capacity Settings
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">

        <div class="alert alert-info d-flex align-items-center gap-2 mb-4" style="font-size:.875rem">
            <i class="bi bi-info-circle-fill flex-shrink-0"></i>
            <div>
                These rates define how many units each department can produce in a single working day.
                Saving will immediately rebuild all production schedules.
            </div>
        </div>

        <form method="POST" action="{{ route('capacity.update') }}">
            @csrf
            @method('PUT')

            @foreach(['print' => ['label' => 'Printing', 'icon' => 'bi-printer', 'color' => 'warning'], 'sew' => ['label' => 'Sewing', 'icon' => 'bi-scissors', 'color' => 'primary']] as $dept => $meta)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                        <i class="bi {{ $meta['icon'] }} text-{{ $meta['color'] }} fs-5"></i>
                        <span class="fw-semibold">{{ $meta['label'] }} Department</span>
                        <small class="text-muted ms-1">— units per full working day</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($productTypes as $typeKey => $typeLabel)
                                @php
                                    $existing = $configs[$dept]->firstWhere('product_type', $typeKey);
                                    $value = old("rates.{$dept}.{$typeKey}", $existing?->units_per_day ?? '');
                                @endphp
                                <div class="col-6 col-md-4 col-lg-3">
                                    <label class="form-label fw-semibold" style="font-size:.85rem">
                                        {{ $typeLabel }}
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="number"
                                               name="rates[{{ $dept }}][{{ $typeKey }}]"
                                               class="form-control @error("rates.{$dept}.{$typeKey}") is-invalid @enderror"
                                               value="{{ $value }}"
                                               min="1"
                                               max="9999"
                                               required>
                                        <span class="input-group-text text-muted">/day</span>
                                    </div>
                                    @error("rates.{$dept}.{$typeKey}")
                                        <div class="text-danger" style="font-size:.75rem">{{ $message }}</div>
                                    @enderror
                                    @if($existing?->updatedBy)
                                        <div class="text-muted mt-1" style="font-size:.7rem">
                                            Updated by {{ $existing->updatedBy->name }}
                                            {{ $existing->updated_at->diffForHumans() }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save & Rebuild Schedules
                </button>
                <a href="{{ route('dashboard.pipeline') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>

    </div>
</div>

@endsection
