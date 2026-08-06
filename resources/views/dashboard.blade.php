@extends('layouts.app')

@section('title', __('dashboard.dashboard'))

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? __('ui.good_morning') : ($hour < 17 ? __('ui.good_afternoon') : __('ui.good_evening'));
    $firstName = str(auth()->user()?->name ?? '')->before(' ')->toString();

    // Punctuation and word order differ per language, so the whole phrase is a
    // translated pattern rather than a string concatenated in the view —
    // "Greeting, Name" reorders incorrectly under bidirectional text.
    $greetingLine = $firstName === ''
        ? $greeting
        : __('ui.greeting_named', ['greeting' => $greeting, 'name' => $firstName]);

    $quranRate = (int) ($todayQuran['percentage'] ?? 0);
    $salahRate = (int) ($todaySalah['percentage'] ?? 0);

    $rateTone = static fn (int $rate): string => match (true) {
        $rate >= 85 => 'success',
        $rate >= 60 => 'warning',
        default => 'danger',
    };
@endphp

<x-page-header :title="$greetingLine"
               :subtitle="__('ui.today_is', ['date' => now()->translatedFormat('l, d F Y')])"
               icon="bi-grid-1x2">
    <x-slot:actions>
        @can('quran.attendance.create')
            <a href="{{ route('quran-attendance.create') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clipboard-plus" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">{{ __('dashboard.mark_quran_attendance') }}</span>
                <span class="d-sm-none">{{ __('ui.mark_attendance') }}</span>
            </a>
        @endcan
        @can('salah.attendance.create')
            <a href="{{ route('salah-attendance.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                <span>{{ __('dashboard.mark_salah_attendance') }}</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- ── Key figures ──────────────────────────────────────────────────────── --}}
@canany(['employee.view', 'teacher.view', 'quran.class.view', 'jamaat.view'])
    <div class="auto-grid auto-grid--sm mb-4">
        @can('employee.view')
            @php($inactive = max(0, $overview['total_employees'] - $overview['active_employees']))
            <x-stat-card
                :label="__('dashboard.employees')"
                :value="number_format($overview['total_employees'])"
                icon="bi-people"
                tone="primary"
                :href="route('employees.index')"
                :hint="__('ui.view_all')">
                <x-slot:meta>
                    <span class="badge-soft badge-soft-success">{{ number_format($overview['active_employees']) }} {{ __('dashboard.active') }}</span>
                    @if ($inactive > 0)
                        <span class="badge-soft badge-soft-neutral">{{ number_format($inactive) }} {{ __('dashboard.inactive') }}</span>
                    @endif
                </x-slot:meta>
            </x-stat-card>
        @endcan

        @can('teacher.view')
            @php($inactive = max(0, $overview['total_teachers'] - $overview['active_teachers']))
            <x-stat-card
                :label="__('dashboard.teachers')"
                :value="number_format($overview['total_teachers'])"
                icon="bi-mortarboard"
                tone="info"
                :href="route('teachers.index')"
                :hint="__('ui.view_all')">
                <x-slot:meta>
                    <span class="badge-soft badge-soft-success">{{ number_format($overview['active_teachers']) }} {{ __('dashboard.active') }}</span>
                    @if ($inactive > 0)
                        <span class="badge-soft badge-soft-neutral">{{ number_format($inactive) }} {{ __('dashboard.inactive') }}</span>
                    @endif
                </x-slot:meta>
            </x-stat-card>
        @endcan

        @can('quran.class.view')
            @php($inactive = max(0, $overview['total_quran_classes'] - $overview['active_quran_classes']))
            <x-stat-card
                :label="__('dashboard.quran_classes')"
                :value="number_format($overview['total_quran_classes'])"
                icon="bi-book"
                tone="warning"
                :href="route('quran-classes.index')"
                :hint="__('ui.view_all')">
                <x-slot:meta>
                    <span class="badge-soft badge-soft-success">{{ number_format($overview['active_quran_classes']) }} {{ __('dashboard.active') }}</span>
                    @if ($inactive > 0)
                        <span class="badge-soft badge-soft-neutral">{{ number_format($inactive) }} {{ __('dashboard.inactive') }}</span>
                    @endif
                </x-slot:meta>
            </x-stat-card>
        @endcan

        @can('jamaat.view')
            @php($inactive = max(0, $overview['total_jamaats'] - $overview['active_jamaats']))
            <x-stat-card
                :label="__('dashboard.jamaats')"
                :value="number_format($overview['total_jamaats'])"
                icon="bi-people-fill"
                tone="accent"
                :href="route('jamaats.index')"
                :hint="__('ui.view_all')">
                <x-slot:meta>
                    <span class="badge-soft badge-soft-success">{{ number_format($overview['active_jamaats']) }} {{ __('dashboard.active') }}</span>
                    @if ($inactive > 0)
                        <span class="badge-soft badge-soft-neutral">{{ number_format($inactive) }} {{ __('dashboard.inactive') }}</span>
                    @endif
                </x-slot:meta>
            </x-stat-card>
        @endcan
    </div>
@endcanany

{{-- ── Today's attendance ───────────────────────────────────────────────── --}}
@canany(['quran.attendance.view', 'salah.attendance.view'])
    <h2 class="section-heading">
        <i class="bi bi-calendar-check" aria-hidden="true"></i>{{ __('dashboard.today_attendance') }}
    </h2>

    <div class="row g-3 mb-2">
        @can('quran.attendance.view')
            <div class="col-12 col-xl-6">
                <x-card :title="__('dashboard.quran_attendance_today')" icon="bi-book" class="h-100">
                    <x-slot:actions>
                        @if ($todayQuran['total'] > 0)
                            <span class="badge-soft badge-soft-{{ $rateTone($quranRate) }}">{{ $quranRate }}%</span>
                        @endif
                        <a href="{{ route('quran-attendance.index') }}" class="btn btn-sm btn-ghost">
                            {{ __('ui.view_all') }}<i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </x-slot:actions>

                    @if ($todayQuran['total'] > 0)
                        <div class="metric-strip mb-3">
                            <div class="metric-strip__item">
                                <div class="metric-strip__value">{{ number_format($todayQuran['total']) }}</div>
                                <div class="metric-strip__label">{{ __('dashboard.total') }}</div>
                            </div>
                            <div class="metric-strip__item">
                                <div class="metric-strip__value text-success">{{ number_format($todayQuran['present']) }}</div>
                                <div class="metric-strip__label">{{ __('dashboard.present') }}</div>
                            </div>
                            <div class="metric-strip__item">
                                <div class="metric-strip__value text-danger">{{ number_format($todayQuran['absent']) }}</div>
                                <div class="metric-strip__label">{{ __('dashboard.absent') }}</div>
                            </div>
                            <div class="metric-strip__item">
                                <div class="metric-strip__value">{{ $quranRate }}%</div>
                                <div class="metric-strip__label">{{ __('dashboard.attendance_pct') }}</div>
                            </div>
                        </div>

                        <div class="progress progress--split"
                             role="img"
                             aria-label="{{ __('ui.attendance_progress', ['present' => $todayQuran['present'], 'total' => $todayQuran['total']]) }}">
                            <div class="progress-bar bg-success" style="width: {{ $quranRate }}%"></div>
                            <div class="progress-bar bg-danger" style="width: {{ 100 - $quranRate }}%"></div>
                        </div>
                    @else
                        <x-empty-state size="sm"
                                       icon="bi-clipboard-x"
                                       :title="__('dashboard.no_attendance_today')"
                                       :text="__('ui.no_data_yet')">
                            @can('quran.attendance.create')
                                <a href="{{ route('quran-attendance.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-clipboard-plus" aria-hidden="true"></i>
                                    <span>{{ __('dashboard.mark_quran_attendance') }}</span>
                                </a>
                            @endcan
                        </x-empty-state>
                    @endif
                </x-card>
            </div>
        @endcan

        @can('salah.attendance.view')
            <div class="col-12 col-xl-6">
                <x-card :title="__('dashboard.salah_attendance_today')" icon="bi-moon-stars" class="h-100">
                    <x-slot:actions>
                        @if ($todaySalah['total'] > 0)
                            <span class="badge-soft badge-soft-{{ $rateTone($salahRate) }}">{{ $salahRate }}%</span>
                        @endif
                        <a href="{{ route('salah-attendance.index') }}" class="btn btn-sm btn-ghost">
                            {{ __('ui.view_all') }}<i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </x-slot:actions>

                    @if ($todaySalah['total'] > 0)
                        <div class="metric-strip mb-3">
                            <div class="metric-strip__item">
                                <div class="metric-strip__value">{{ number_format($todaySalah['total']) }}</div>
                                <div class="metric-strip__label">{{ __('dashboard.total') }}</div>
                            </div>
                            <div class="metric-strip__item">
                                <div class="metric-strip__value text-success">{{ number_format($todaySalah['present']) }}</div>
                                <div class="metric-strip__label">{{ __('dashboard.present') }}</div>
                            </div>
                            <div class="metric-strip__item">
                                <div class="metric-strip__value text-danger">{{ number_format($todaySalah['absent']) }}</div>
                                <div class="metric-strip__label">{{ __('dashboard.absent') }}</div>
                            </div>
                            <div class="metric-strip__item">
                                <div class="metric-strip__value">{{ $salahRate }}%</div>
                                <div class="metric-strip__label">{{ __('dashboard.attendance_pct') }}</div>
                            </div>
                        </div>

                        <div class="progress progress--split"
                             role="img"
                             aria-label="{{ __('ui.attendance_progress', ['present' => $todaySalah['present'], 'total' => $todaySalah['total']]) }}">
                            <div class="progress-bar bg-success" style="width: {{ $salahRate }}%"></div>
                            <div class="progress-bar bg-danger" style="width: {{ 100 - $salahRate }}%"></div>
                        </div>
                    @else
                        <x-empty-state size="sm"
                                       icon="bi-calendar-x"
                                       :title="__('dashboard.no_attendance_today')"
                                       :text="__('ui.no_data_yet')">
                            @can('salah.attendance.create')
                                <a href="{{ route('salah-attendance.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                    <span>{{ __('dashboard.mark_salah_attendance') }}</span>
                                </a>
                            @endcan
                        </x-empty-state>
                    @endif
                </x-card>
            </div>
        @endcan
    </div>
@endcanany

{{-- ── Attendance trend ─────────────────────────────────────────────────── --}}
@canany(['quran.attendance.view', 'salah.attendance.view'])
    <h2 class="section-heading">
        <i class="bi bi-graph-up" aria-hidden="true"></i>{{ __('dashboard.trend_title') }}
    </h2>

    <x-card class="mb-2">
        <x-slot:title>{{ __('dashboard.trend_title') }}</x-slot:title>
        <x-slot:subtitle>{{ __('dashboard.trend_subtitle') }}</x-slot:subtitle>

        <x-trend-chart :trend="$trend" />
    </x-card>
@endcanany

{{-- ── Module summary ───────────────────────────────────────────────────── --}}
@canany(['quran.progress.view', 'salah.attendance.view'])
    <h2 class="section-heading">
        <i class="bi bi-bar-chart" aria-hidden="true"></i>{{ __('dashboard.module_summary') }}
    </h2>

    <div class="row g-3">
        @can('quran.progress.view')
            @php($completion = (int) ($quranSummary['avg_completion'] ?? 0))
            <div class="col-12 col-md-6 col-xl-4">
                <x-card class="h-100" body-class="text-center">
                    <div class="meter mb-3"
                         style="--value: {{ $completion }}; --tone: var(--rams-primary);"
                         role="img"
                         aria-label="{{ __('dashboard.avg_quran_completion') }}: {{ $completion }}%">
                        <span class="meter__label">{{ $completion }}%</span>
                    </div>
                    <p class="fw-semibold mb-1">{{ __('dashboard.avg_quran_completion') }}</p>
                    <p class="fs-sm text-soft mb-0">{{ __('quran_progress.quran_progress') }}</p>
                </x-card>
            </div>

            <div class="col-12 col-md-6 col-xl-4">
                <x-stat-card
                    :label="__('dashboard.quran_progress_records')"
                    :value="number_format($quranSummary['total_progress_records'])"
                    icon="bi-journal-check"
                    tone="primary"
                    :href="route('quran-progress.index')"
                    :hint="__('ui.view_all')"
                    class="h-100" />
            </div>
        @endcan

        @can('salah.attendance.view')
            <div class="col-12 col-md-6 col-xl-4">
                <x-stat-card
                    :label="__('dashboard.total_salah_records')"
                    :value="number_format($salahSummary['total_attendance_records'])"
                    icon="bi-calendar-check"
                    tone="info"
                    :href="route('salah-attendance.index')"
                    :hint="__('ui.view_all')"
                    class="h-100" />
            </div>
        @endcan
    </div>
@endcanany

{{-- ── Quick actions ────────────────────────────────────────────────────── --}}
@canany(['quran.attendance.create', 'salah.attendance.create', 'employee.create', 'report.employee', 'report.teacher', 'report.quran', 'report.salah'])
    <h2 class="section-heading">
        <i class="bi bi-lightning-charge" aria-hidden="true"></i>{{ __('dashboard.quick_actions') }}
    </h2>

    {{-- Wider minimum than the KPI row: these tiles carry two lines of text and
         must not be squeezed into a column that breaks words. --}}
    <div class="auto-grid">
        @can('quran.attendance.create')
            <a href="{{ route('quran-attendance.create') }}" class="card card-link-tile tone-success">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-clipboard-plus"></i></span>
                    <span class="min-w-0">
                        <span class="d-block fw-semibold fs-md">{{ __('dashboard.mark_quran_attendance') }}</span>
                        <span class="d-block fs-xs text-soft">{{ __('quran_attendance.quran_attendance') }}</span>
                    </span>
                    <i class="bi bi-arrow-right ms-auto text-soft" aria-hidden="true"></i>
                </div>
            </a>
        @endcan

        @can('salah.attendance.create')
            <a href="{{ route('salah-attendance.create') }}" class="card card-link-tile tone-primary">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-calendar-plus"></i></span>
                    <span class="min-w-0">
                        <span class="d-block fw-semibold fs-md">{{ __('dashboard.mark_salah_attendance') }}</span>
                        <span class="d-block fs-xs text-soft">{{ __('salah_attendance.salah_attendance') }}</span>
                    </span>
                    <i class="bi bi-arrow-right ms-auto text-soft" aria-hidden="true"></i>
                </div>
            </a>
        @endcan

        @can('employee.create')
            <a href="{{ route('employees.create') }}" class="card card-link-tile tone-warning">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                    <span class="min-w-0">
                        <span class="d-block fw-semibold fs-md">{{ __('dashboard.add_employee') }}</span>
                        <span class="d-block fs-xs text-soft">{{ __('employees.employees') }}</span>
                    </span>
                    <i class="bi bi-arrow-right ms-auto text-soft" aria-hidden="true"></i>
                </div>
            </a>
        @endcan

        @canany(['report.employee', 'report.teacher', 'report.quran', 'report.salah'])
            <a href="{{ route('reports.index') }}" class="card card-link-tile tone-info">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-bar-chart"></i></span>
                    <span class="min-w-0">
                        <span class="d-block fw-semibold fs-md">{{ __('dashboard.view_reports') }}</span>
                        <span class="d-block fs-xs text-soft">{{ __('reports.report_center') }}</span>
                    </span>
                    <i class="bi bi-arrow-right ms-auto text-soft" aria-hidden="true"></i>
                </div>
            </a>
        @endcanany
    </div>
@endcanany
@endsection
