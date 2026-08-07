@extends('layouts.app')

@section('title', __('reports.report_center'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.report_center') }}</li>
@endsection

@section('content')
@php
    // Declaring the catalogue as data keeps the tiles consistent and makes
    // adding a report a one-line change.
    $reports = [
        // First, because it is the one that answers questions the fixed
        // reports below cannot: any breakdown, any combination of filters.
        ['permission' => ['report.quran', 'report.salah'], 'route' => 'reports.analysis.index', 'icon' => 'bi-diagram-3', 'tone' => 'accent',
         'title' => __('analytics.title'), 'desc' => __('analytics.subtitle')],
        ['permission' => 'report.dashboard', 'route' => 'reports.dashboard', 'icon' => 'bi-pie-chart', 'tone' => 'primary',
         'title' => __('reports.dashboard_summary'), 'desc' => __('reports.dashboard_summary_desc')],
        ['permission' => 'report.employee', 'route' => 'reports.employees', 'icon' => 'bi-people', 'tone' => 'info',
         'title' => __('reports.employee_report'), 'desc' => __('reports.employee_report_desc')],
        ['permission' => 'report.teacher', 'route' => 'reports.teachers', 'icon' => 'bi-mortarboard', 'tone' => 'accent',
         'title' => __('reports.teacher_report'), 'desc' => __('reports.teacher_report_desc')],
        ['permission' => 'report.quran', 'route' => 'reports.quran-attendance', 'icon' => 'bi-journal-check', 'tone' => 'success',
         'title' => __('reports.quran_attendance_report'), 'desc' => __('reports.quran_attendance_report_desc')],
        ['permission' => 'report.quran', 'route' => 'reports.quran-progress', 'icon' => 'bi-graph-up-arrow', 'tone' => 'warning',
         'title' => __('reports.quran_progress_report'), 'desc' => __('reports.quran_progress_report_desc')],
        ['permission' => 'report.salah', 'route' => 'reports.salah-attendance', 'icon' => 'bi-calendar-check', 'tone' => 'primary',
         'title' => __('reports.salah_attendance_report'), 'desc' => __('reports.salah_attendance_report_desc')],
    ];

    // A tile may accept any one of several permissions — the analysis screen
    // opens for a Quran reader or a Salah reader, and shows them only the
    // datasets they hold.
    $visible = array_values(array_filter($reports, function (array $report): bool {
        foreach ((array) $report['permission'] as $ability) {
            if (auth()->user()?->can($ability)) {
                return true;
            }
        }

        return false;
    }));
@endphp

<x-page-header :title="__('reports.report_center')"
               :subtitle="__('reports.center_subtitle')"
               icon="bi-collection" />

@if (empty($visible))
    <x-card>
        <x-empty-state icon="bi-lock"
                       :title="__('reports.no_reports_title')"
                       :text="__('reports.no_reports_text')" />
    </x-card>
@else
    <div class="auto-grid auto-grid--lg">
        @foreach ($visible as $report)
            <a href="{{ route($report['route']) }}" class="card card-link-tile tone-{{ $report['tone'] }}">
                <div class="card-body">
                    <span class="stat-card__icon mb-3" aria-hidden="true"><i class="bi {{ $report['icon'] }}"></i></span>

                    <h2 class="h6 mb-1">{{ $report['title'] }}</h2>
                    <p class="fs-sm text-soft mb-3">{{ $report['desc'] }}</p>

                    <span class="fs-sm fw-semibold text-brand d-inline-flex align-items-center gap-1">
                        {{ __('reports.view_report') }}
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="alert alert-info mt-4" role="note">
        <i class="bi bi-lightbulb-fill alert__icon" aria-hidden="true"></i>
        <div class="alert__body">
            <strong class="alert__title">{{ __('reports.tip_title') }}</strong>
            {{ __('reports.tip_text') }}
        </div>
    </div>
@endif
@endsection
