@extends('layouts.app')

@section('title', 'Print Performance')
@section('page-title')
    <i class="bi bi-graph-up-arrow me-2 text-warning"></i>Print Performance
@endsection

@section('content')

{{-- ── Tab nav ─────────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.printing') }}">
            <i class="bi bi-printer me-1"></i>Queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard.printing.performance') }}">
            <i class="bi bi-bar-chart-line me-1"></i>Performance
        </a>
    </li>
</ul>

@include('partials.perf-charts', [
    'perf'        => $perf,
    'perfMine'    => $perfMine,
    'accentColor' => '#ffc107',
    'accentRgb'   => '255,193,7',
])

@endsection
