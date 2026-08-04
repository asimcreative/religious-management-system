@extends('layouts.app')

@section('title', __('reports.dashboard_summary'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('reports.dashboard_summary') }}</h4>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('reports.back_to_reports') }}
    </a>
</div>

<div class="row g-3">
    {{-- Employees --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-people fs-2 text-primary"></i>
                <h3 class="mt-2 mb-0">{{ $summary['total_employees'] }}</h3>
                <small class="text-muted">{{ __('reports.total_employees') }}</small>
                <br>
                <span class="badge bg-success">{{ $summary['active_employees'] }} {{ __('reports.active_employees') }}</span>
            </div>
        </div>
    </div>

    {{-- Teachers --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-mortarboard fs-2 text-info"></i>
                <h3 class="mt-2 mb-0">{{ $summary['total_teachers'] }}</h3>
                <small class="text-muted">{{ __('reports.total_teachers') }}</small>
                <br>
                <span class="badge bg-success">{{ $summary['active_teachers'] }} {{ __('reports.active_teachers') }}</span>
            </div>
        </div>
    </div>

    {{-- Quran Classes --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-book fs-2 text-warning"></i>
                <h3 class="mt-2 mb-0">{{ $summary['total_quran_classes'] }}</h3>
                <small class="text-muted">{{ __('reports.total_quran_classes') }}</small>
                <br>
                <span class="badge bg-success">{{ $summary['active_quran_classes'] }} {{ __('reports.active_quran_classes') }}</span>
            </div>
        </div>
    </div>

    {{-- Jamaats --}}
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-moon fs-2 text-secondary"></i>
                <h3 class="mt-2 mb-0">{{ $summary['total_jamaats'] }}</h3>
                <small class="text-muted">{{ __('reports.total_jamaats') }}</small>
                <br>
                <span class="badge bg-success">{{ $summary['active_jamaats'] }} {{ __('reports.active_jamaats') }}</span>
            </div>
        </div>
    </div>

    {{-- Quran Attendance --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-clipboard-check fs-2 text-success"></i>
                <h3 class="mt-2 mb-0">{{ number_format($summary['total_quran_attendance']) }}</h3>
                <small class="text-muted">{{ __('reports.total_quran_attendance') }}</small>
            </div>
        </div>
    </div>

    {{-- Salah Attendance --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-calendar-check fs-2 text-primary"></i>
                <h3 class="mt-2 mb-0">{{ number_format($summary['total_salah_attendance']) }}</h3>
                <small class="text-muted">{{ __('reports.total_salah_attendance') }}</small>
            </div>
        </div>
    </div>

    {{-- Quran Progress --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-graph-up fs-2 text-danger"></i>
                <h3 class="mt-2 mb-0">{{ number_format($summary['total_quran_progress']) }}</h3>
                <small class="text-muted">{{ __('reports.total_quran_progress') }}</small>
            </div>
        </div>
    </div>
</div>
@endsection
