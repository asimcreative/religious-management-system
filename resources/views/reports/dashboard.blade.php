@extends('layouts.app')

@section('title', __('reports.dashboard_summary'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.reports') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.dashboard_summary') }}</li>
@endsection

@section('content')
@php
    $share = static fn (int $active, int $total): int => $total > 0 ? (int) round($active / $total * 100) : 0;

    $entities = [
        ['label' => __('reports.total_employees'), 'total' => $summary['total_employees'], 'active' => $summary['active_employees'],
         'activeLabel' => __('reports.active_employees'), 'icon' => 'bi-people', 'tone' => 'primary'],
        ['label' => __('reports.total_teachers'), 'total' => $summary['total_teachers'], 'active' => $summary['active_teachers'],
         'activeLabel' => __('reports.active_teachers'), 'icon' => 'bi-mortarboard', 'tone' => 'info'],
        ['label' => __('reports.total_quran_classes'), 'total' => $summary['total_quran_classes'], 'active' => $summary['active_quran_classes'],
         'activeLabel' => __('reports.active_quran_classes'), 'icon' => 'bi-book', 'tone' => 'warning'],
        ['label' => __('reports.total_jamaats'), 'total' => $summary['total_jamaats'], 'active' => $summary['active_jamaats'],
         'activeLabel' => __('reports.active_jamaats'), 'icon' => 'bi-people-fill', 'tone' => 'accent'],
    ];

    $volumes = [
        ['label' => __('reports.total_quran_attendance'), 'value' => $summary['total_quran_attendance'], 'icon' => 'bi-clipboard-check', 'tone' => 'success'],
        ['label' => __('reports.total_salah_attendance'), 'value' => $summary['total_salah_attendance'], 'icon' => 'bi-calendar-check', 'tone' => 'primary'],
        ['label' => __('reports.total_quran_progress'), 'value' => $summary['total_quran_progress'], 'icon' => 'bi-graph-up-arrow', 'tone' => 'warning'],
    ];
@endphp

<x-page-header :title="__('reports.dashboard_summary')"
               :subtitle="__('reports.dashboard_summary_desc')"
               icon="bi-pie-chart">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>{{ __('ui.print') }}</span>
        </button>

        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('reports.back_to_reports') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="__('reports.dashboard_summary')" :subtitle="__('reports.dashboard_summary_desc')" />

<h2 class="section-heading"><i class="bi bi-diagram-3" aria-hidden="true"></i>{{ __('reports.records_heading') }}</h2>

<div class="auto-grid auto-grid--sm mb-4">
    @foreach ($entities as $entity)
        @php($pct = $share((int) $entity['active'], (int) $entity['total']))
        <x-stat-card :label="$entity['label']"
                     :value="number_format($entity['total'])"
                     :icon="$entity['icon']"
                     :tone="$entity['tone']">
            <x-slot:meta>
                <span class="badge-soft badge-soft-success">
                    {{ number_format($entity['active']) }} {{ $entity['activeLabel'] }}
                </span>
                <span class="text-subtle">·</span>
                <span class="tabular">{{ $pct }}%</span>
            </x-slot:meta>
        </x-stat-card>
    @endforeach
</div>

<h2 class="section-heading"><i class="bi bi-database" aria-hidden="true"></i>{{ __('reports.volume_heading') }}</h2>

<div class="auto-grid auto-grid--sm mb-4">
    @foreach ($volumes as $volume)
        <x-stat-card :label="$volume['label']"
                     :value="number_format($volume['value'])"
                     :icon="$volume['icon']"
                     :tone="$volume['tone']" />
    @endforeach
</div>

{{-- Tabular restatement: the same figures in a form that prints and exports
     cleanly, and that screen-reader users can navigate as a table. --}}
<x-card :title="__('reports.summary')" icon="bi-table" flush>
    <x-table :label="__('reports.dashboard_summary')">
        <thead>
            <tr>
                <th scope="col">{{ __('reports.summary') }}</th>
                <th scope="col" class="col-fit">{{ __('reports.total') }}</th>
                <th scope="col" class="col-fit">{{ __('masters.active') }}</th>
                <th scope="col" style="min-width: 9rem;">{{ __('reports.attendance_rate') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entities as $entity)
                @php($pct = $share((int) $entity['active'], (int) $entity['total']))
                <tr>
                    <td data-label="{{ __('reports.summary') }}" class="fw-semibold text-strong">{{ $entity['label'] }}</td>
                    <td data-label="{{ __('reports.total') }}" class="col-fit tabular">{{ number_format($entity['total']) }}</td>
                    <td data-label="{{ __('masters.active') }}" class="col-fit tabular">{{ number_format($entity['active']) }}</td>
                    <td data-label="{{ __('reports.attendance_rate') }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="progress flex-grow-1" style="max-width: 10rem;">
                                <span class="progress-bar bg-success" style="width: {{ $pct }}%"></span>
                            </span>
                            <span class="tabular fs-sm">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
            @endforeach

            @foreach ($volumes as $volume)
                <tr>
                    <td data-label="{{ __('reports.summary') }}" class="fw-semibold text-strong">{{ $volume['label'] }}</td>
                    <td data-label="{{ __('reports.total') }}" class="col-fit tabular">{{ number_format($volume['value']) }}</td>
                    <td data-label="{{ __('masters.active') }}"><span class="dash">—</span></td>
                    <td data-label="{{ __('reports.attendance_rate') }}"><span class="dash">—</span></td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</x-card>
@endsection
