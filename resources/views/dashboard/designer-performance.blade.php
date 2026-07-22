@extends('layouts.app')

@section('title', 'Design Performance')
@section('page-title')
    <i class="bi bi-graph-up-arrow me-2 text-info"></i>Design Performance
@endsection

@section('content')

{{-- ── Tab nav ─────────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard.designer') }}">
            <i class="bi bi-pencil-square me-1"></i>Queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard.designer.performance') }}">
            <i class="bi bi-bar-chart-line me-1"></i>Performance
        </a>
    </li>
</ul>

@include('partials.perf-charts', [
    'perf'        => $perf,
    'perfMine'    => $perfMine,
    'from'        => $from,
    'to'          => $to,
    'period'      => $period,
    'accentColor' => '#0dcaf0',
    'accentRgb'   => '13,202,240',
])

@endsection
