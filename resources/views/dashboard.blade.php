@extends('layouts.app')

@section('title', __('dashboard.dashboard'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('dashboard.dashboard') }}</h4>
    <small class="text-muted">{{ __('dashboard.welcome', ['name' => Auth::user()?->name ?? '']) }}</small>
</div>

{{-- Overview KPI Cards --}}
@canany(['employee.view', 'teacher.view', 'quran.class.view', 'jamaat.view'])
    <div class="row g-3 mb-4">
        @can('employee.view')
            <div class="col-md-3">
                <div class="card border-start border-primary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">{{ __('dashboard.employees') }}</div>
                                <h3 class="mb-0">{{ $overview['total_employees'] }}</h3>
                            </div>
                            <i class="bi bi-people fs-1 text-primary opacity-25"></i>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-success">{{ $overview['active_employees'] }} {{ __('dashboard.active') }}</span>
                            <span class="badge bg-secondary">{{ $overview['total_employees'] - $overview['active_employees'] }} {{ __('dashboard.inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('teacher.view')
            <div class="col-md-3">
                <div class="card border-start border-info border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">{{ __('dashboard.teachers') }}</div>
                                <h3 class="mb-0">{{ $overview['total_teachers'] }}</h3>
                            </div>
                            <i class="bi bi-mortarboard fs-1 text-info opacity-25"></i>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-success">{{ $overview['active_teachers'] }} {{ __('dashboard.active') }}</span>
                            <span class="badge bg-secondary">{{ $overview['total_teachers'] - $overview['active_teachers'] }} {{ __('dashboard.inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('quran.class.view')
            <div class="col-md-3">
                <div class="card border-start border-warning border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">{{ __('dashboard.quran_classes') }}</div>
                                <h3 class="mb-0">{{ $overview['total_quran_classes'] }}</h3>
                            </div>
                            <i class="bi bi-book fs-1 text-warning opacity-25"></i>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-success">{{ $overview['active_quran_classes'] }} {{ __('dashboard.active') }}</span>
                            <span class="badge bg-secondary">{{ $overview['total_quran_classes'] - $overview['active_quran_classes'] }} {{ __('dashboard.inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @can('jamaat.view')
            <div class="col-md-3">
                <div class="card border-start border-secondary border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small">{{ __('dashboard.jamaats') }}</div>
                                <h3 class="mb-0">{{ $overview['total_jamaats'] }}</h3>
                            </div>
                            <i class="bi bi-moon fs-1 text-secondary opacity-25"></i>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-success">{{ $overview['active_jamaats'] }} {{ __('dashboard.active') }}</span>
                            <span class="badge bg-secondary">{{ $overview['total_jamaats'] - $overview['active_jamaats'] }} {{ __('dashboard.inactive') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endcanany

{{-- Today's Attendance --}}
@canany(['quran.attendance.view', 'salah.attendance.view'])
    <h5 class="mb-3"><i class="bi bi-calendar-check me-1"></i> {{ __('dashboard.today_attendance') }}</h5>
    <div class="row g-3 mb-4">
        @can('quran.attendance.view')
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <strong><i class="bi bi-book me-1"></i> {{ __('dashboard.quran_attendance_today') }}</strong>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-3">
                                <h4 class="mb-0">{{ $todayQuran['total'] }}</h4>
                                <small class="text-muted">{{ __('dashboard.total') }}</small>
                            </div>
                            <div class="col-3">
                                <h4 class="mb-0 text-success">{{ $todayQuran['present'] }}</h4>
                                <small class="text-muted">{{ __('dashboard.present') }}</small>
                            </div>
                            <div class="col-3">
                                <h4 class="mb-0 text-danger">{{ $todayQuran['absent'] }}</h4>
                                <small class="text-muted">{{ __('dashboard.absent') }}</small>
                            </div>
                            <div class="col-3">
                                <h4 class="mb-0 text-primary">{{ $todayQuran['percentage'] }}%</h4>
                                <small class="text-muted">{{ __('dashboard.attendance_pct') }}</small>
                            </div>
                        </div>
                        @if($todayQuran['total'] > 0)
                            <div class="progress mt-3" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $todayQuran['percentage'] }}%"></div>
                                <div class="progress-bar bg-danger" style="width: {{ 100 - $todayQuran['percentage'] }}%"></div>
                            </div>
                        @else
                            <div class="text-center text-muted mt-3">{{ __('dashboard.no_attendance_today') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endcan

        @can('salah.attendance.view')
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <strong><i class="bi bi-moon me-1"></i> {{ __('dashboard.salah_attendance_today') }}</strong>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-3">
                                <h4 class="mb-0">{{ $todaySalah['total'] }}</h4>
                                <small class="text-muted">{{ __('dashboard.total') }}</small>
                            </div>
                            <div class="col-3">
                                <h4 class="mb-0 text-success">{{ $todaySalah['present'] }}</h4>
                                <small class="text-muted">{{ __('dashboard.present') }}</small>
                            </div>
                            <div class="col-3">
                                <h4 class="mb-0 text-danger">{{ $todaySalah['absent'] }}</h4>
                                <small class="text-muted">{{ __('dashboard.absent') }}</small>
                            </div>
                            <div class="col-3">
                                <h4 class="mb-0 text-primary">{{ $todaySalah['percentage'] }}%</h4>
                                <small class="text-muted">{{ __('dashboard.attendance_pct') }}</small>
                            </div>
                        </div>
                        @if($todaySalah['total'] > 0)
                            <div class="progress mt-3" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $todaySalah['percentage'] }}%"></div>
                                <div class="progress-bar bg-danger" style="width: {{ 100 - $todaySalah['percentage'] }}%"></div>
                            </div>
                        @else
                            <div class="text-center text-muted mt-3">{{ __('dashboard.no_attendance_today') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endcanany

{{-- Module Summaries --}}
@canany(['quran.progress.view', 'salah.attendance.view'])
    <h5 class="mb-3"><i class="bi bi-bar-chart me-1"></i> {{ __('dashboard.module_summary') }}</h5>
    <div class="row g-3 mb-4">
        @can('quran.progress.view')
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-graph-up fs-2 text-success"></i>
                        <h3 class="mt-2 mb-0">{{ $quranSummary['avg_completion'] }}%</h3>
                        <small class="text-muted">{{ __('dashboard.avg_quran_completion') }}</small>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: {{ $quranSummary['avg_completion'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-journal-check fs-2 text-primary"></i>
                        <h3 class="mt-2 mb-0">{{ number_format($quranSummary['total_progress_records']) }}</h3>
                        <small class="text-muted">{{ __('dashboard.quran_progress_records') }}</small>
                    </div>
                </div>
            </div>
        @endcan

        @can('salah.attendance.view')
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check fs-2 text-info"></i>
                        <h3 class="mt-2 mb-0">{{ number_format($salahSummary['total_attendance_records']) }}</h3>
                        <small class="text-muted">{{ __('dashboard.total_salah_records') }}</small>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endcanany

{{-- Quick Actions --}}
<h5 class="mb-3"><i class="bi bi-lightning me-1"></i> {{ __('dashboard.quick_actions') }}</h5>
<div class="row g-3">
    @can('quran.attendance.create')
        <div class="col-md-3">
            <a href="{{ route('quran-attendance.create') }}" class="card text-decoration-none">
                <div class="card-body text-center py-3">
                    <i class="bi bi-clipboard-plus fs-3 text-success"></i>
                    <div class="mt-1 small">{{ __('dashboard.mark_quran_attendance') }}</div>
                </div>
            </a>
        </div>
    @endcan

    @can('salah.attendance.create')
        <div class="col-md-3">
            <a href="{{ route('salah-attendance.create') }}" class="card text-decoration-none">
                <div class="card-body text-center py-3">
                    <i class="bi bi-calendar-plus fs-3 text-primary"></i>
                    <div class="mt-1 small">{{ __('dashboard.mark_salah_attendance') }}</div>
                </div>
            </a>
        </div>
    @endcan

    @can('employee.create')
        <div class="col-md-3">
            <a href="{{ route('employees.create') }}" class="card text-decoration-none">
                <div class="card-body text-center py-3">
                    <i class="bi bi-person-plus fs-3 text-warning"></i>
                    <div class="mt-1 small">{{ __('dashboard.add_employee') }}</div>
                </div>
            </a>
        </div>
    @endcan

    @canany(['report.employee', 'report.teacher', 'report.quran', 'report.salah'])
        <div class="col-md-3">
            <a href="{{ route('reports.index') }}" class="card text-decoration-none">
                <div class="card-body text-center py-3">
                    <i class="bi bi-bar-chart fs-3 text-info"></i>
                    <div class="mt-1 small">{{ __('dashboard.view_reports') }}</div>
                </div>
            </a>
        </div>
    @endcanany
</div>
@endsection
