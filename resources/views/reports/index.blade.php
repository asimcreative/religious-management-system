@extends('layouts.app')

@section('title', __('reports.report_center'))

@section('content')
<h4 class="mb-3">{{ __('reports.report_center') }}</h4>

<div class="row g-3">
    @can('report.employee')
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6><i class="bi bi-people me-1"></i> {{ __('reports.employee_report') }}</h6>
                <p class="text-muted small">{{ __('reports.employee_report_desc') }}</p>
                <a href="{{ route('reports.employees') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> {{ __('reports.view_report') }}
                </a>
            </div>
        </div>
    </div>
    @endcan

    @can('report.teacher')
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6><i class="bi bi-mortarboard me-1"></i> {{ __('reports.teacher_report') }}</h6>
                <p class="text-muted small">{{ __('reports.teacher_report_desc') }}</p>
                <a href="{{ route('reports.teachers') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> {{ __('reports.view_report') }}
                </a>
            </div>
        </div>
    </div>
    @endcan

    @can('report.quran')
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6><i class="bi bi-book me-1"></i> {{ __('reports.quran_attendance_report') }}</h6>
                <p class="text-muted small">{{ __('reports.quran_attendance_report_desc') }}</p>
                <a href="{{ route('reports.quran-attendance') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> {{ __('reports.view_report') }}
                </a>
            </div>
        </div>
    </div>
    @endcan

    @can('report.quran')
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6><i class="bi bi-graph-up me-1"></i> {{ __('reports.quran_progress_report') }}</h6>
                <p class="text-muted small">{{ __('reports.quran_progress_report_desc') }}</p>
                <a href="{{ route('reports.quran-progress') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> {{ __('reports.view_report') }}
                </a>
            </div>
        </div>
    </div>
    @endcan

    @can('report.salah')
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6><i class="bi bi-moon me-1"></i> {{ __('reports.salah_attendance_report') }}</h6>
                <p class="text-muted small">{{ __('reports.salah_attendance_report_desc') }}</p>
                <a href="{{ route('reports.salah-attendance') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> {{ __('reports.view_report') }}
                </a>
            </div>
        </div>
    </div>
    @endcan

    @can('report.dashboard')
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6><i class="bi bi-speedometer2 me-1"></i> {{ __('reports.dashboard_summary') }}</h6>
                <p class="text-muted small">{{ __('reports.dashboard_summary_desc') }}</p>
                <a href="{{ route('reports.dashboard') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> {{ __('reports.view_report') }}
                </a>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection
