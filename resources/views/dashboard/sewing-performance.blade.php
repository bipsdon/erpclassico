@extends('layouts.app')

@section('title', 'Sewing Performance')
@section('page-title')
    <i class="bi bi-graph-up-arrow me-2" style="color:#7c3aed"></i>Sewing Performance
@endsection

@section('content')

{{-- ── Tab nav ─────────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.sewing') }}">
            <i class="bi bi-scissors me-1"></i>Queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard.sewing.performance') }}">
            <i class="bi bi-bar-chart-line me-1"></i>Performance
        </a>
    </li>
</ul>

@include('partials.perf-charts', [
    'perf'        => $perf,
    'perfMine'    => $perfMine,
    'accentColor' => '#7c3aed',
    'accentRgb'   => '124,58,237',
])

@endsection
