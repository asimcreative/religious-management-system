@extends('layouts.app')

@section('title', __('reports.salah_attendance_report'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.reports') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.salah_attendance_report') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];
    if (filled($filters['search'] ?? null))    { $activeFilters += $chip('search', __('ui.search').': '.$filters['search']); }
    if (filled($filters['jamaat_id'] ?? null)) { $activeFilters += $chip('jamaat_id', __('reports.jamaat').': '.($jamaats[$filters['jamaat_id']] ?? '')); }
    if (filled($filters['prayer_id'] ?? null)) { $activeFilters += $chip('prayer_id', __('reports.prayer').': '.($prayers[$filters['prayer_id']] ?? '')); }
    if (filled($filters['date_from'] ?? null)) { $activeFilters += $chip('date_from', __('reports.date_from').': '.$filters['date_from']); }
    if (filled($filters['date_to'] ?? null))   { $activeFilters += $chip('date_to', __('reports.date_to').': '.$filters['date_to']); }

    $rate = (int) ($summary['percentage'] ?? 0);
    $rateTone = $rate >= 85 ? 'success' : ($rate >= 60 ? 'warning' : 'danger');
@endphp

<x-page-header :title="__('reports.salah_attendance_report')"
               :subtitle="__('reports.salah_attendance_report_desc')"
               icon="bi-calendar-check">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>{{ __('ui.print') }}</span>
        </button>

        <a href="{{ route('reports.export.salah-attendance', request()->query()) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
            <span>{{ __('ui.download_excel') }}</span>
        </a>

        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('reports.back_to_reports') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="__('reports.salah_attendance_report')"
                :filters="[
                    __('reports.jamaat') => ($filters['jamaat_id'] ?? null) ? ($jamaats[$filters['jamaat_id']] ?? null) : null,
                    __('reports.prayer') => ($filters['prayer_id'] ?? null) ? ($prayers[$filters['prayer_id']] ?? null) : null,
                    __('reports.date_from') => $filters['date_from'] ?? null,
                    __('reports.date_to') => $filters['date_to'] ?? null,
                ]" />

{{-- ── Executive summary ───────────────────────────────────────────────── --}}
<div class="report-summary mb-4">
    <x-stat-card :label="__('reports.total')" :value="number_format($summary['total'])" icon="bi-list-check" tone="neutral" />
    <x-stat-card :label="__('reports.present')" :value="number_format($summary['present'])" icon="bi-check-circle" tone="success" />
    <x-stat-card :label="__('reports.absent')" :value="number_format($summary['absent'])" icon="bi-x-circle" tone="danger" />
    <x-stat-card :label="__('reports.attendance_rate')" :value="$rate.'%'" icon="bi-percent" :tone="$rateTone">
        <x-slot:meta>
            <span class="progress w-100">
                <span class="progress-bar bg-{{ $rateTone === 'danger' ? 'danger' : ($rateTone === 'warning' ? 'warning' : 'success') }}"
                      style="width: {{ $rate }}%"></span>
            </span>
        </x-slot:meta>
    </x-stat-card>
</div>

{{-- ── Prayer-wise breakdown ───────────────────────────────────────────── --}}
@if ($prayerWise->count() > 0)
    <x-card :title="__('reports.prayer_wise_breakdown')" icon="bi-bar-chart-steps" flush class="mb-4 avoid-break">
        <x-table :label="__('reports.prayer_wise_breakdown')">
            <thead>
                <tr>
                    <th scope="col">{{ __('reports.prayer') }}</th>
                    <th scope="col" class="col-fit">{{ __('reports.total') }}</th>
                    <th scope="col" class="col-fit">{{ __('reports.present') }}</th>
                    <th scope="col" class="col-fit">{{ __('reports.absent') }}</th>
                    <th scope="col" style="min-width: 11rem;">{{ __('reports.attendance_rate') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prayerWise as $pw)
                    @php
                        $pct = $pw->total > 0 ? round(($pw->present / $pw->total) * 100, 1) : 0;
                        $tone = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                    @endphp
                    <tr>
                        <td data-label="{{ __('reports.prayer') }}" class="fw-semibold text-strong">{{ $pw->prayer_name }}</td>
                        <td data-label="{{ __('reports.total') }}" class="col-fit tabular">{{ number_format($pw->total) }}</td>
                        <td data-label="{{ __('reports.present') }}" class="col-fit tabular text-success">{{ number_format($pw->present) }}</td>
                        <td data-label="{{ __('reports.absent') }}" class="col-fit tabular text-danger">{{ number_format($pw->absent) }}</td>
                        <td data-label="{{ __('reports.attendance_rate') }}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="progress flex-grow-1" style="max-width: 12rem;"
                                      role="img" aria-label="{{ $pct }}%">
                                    <span class="progress-bar bg-{{ $tone }}" style="width: {{ $pct }}%"></span>
                                </span>
                                <span class="tabular fw-semibold fs-sm">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
@endif

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('reports.salah-attendance')">
        <div class="flex-grow-1" style="min-width: 12rem;">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('reports.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div style="min-width: 11rem;">
            <label for="jamaat_id" class="form-label">{{ __('reports.jamaat') }}</label>
            <select name="jamaat_id" id="jamaat_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_jamaats') }}</option>
                @foreach ($jamaats as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['jamaat_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 10rem;">
            <label for="prayer_id" class="form-label">{{ __('reports.prayer') }}</label>
            <select name="prayer_id" id="prayer_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_prayers') }}</option>
                @foreach ($prayers as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['prayer_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 9.5rem;">
            <label for="date_from" class="form-label">{{ __('reports.date_from') }}</label>
            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
        </div>

        <div style="min-width: 9.5rem;">
            <label for="date_to" class="form-label">{{ __('reports.date_to') }}</label>
            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
        </div>
    </x-filters>

    <div class="table-toolbar">
        <span>{{ __('reports.total_records') }}: <strong class="text-strong tabular">{{ number_format($attendance->total()) }}</strong></span>
        <span class="table-toolbar__actions fs-xs text-subtle">{{ __('ui.generated_on', ['datetime' => now()->format('d M Y, H:i')]) }}</span>
    </div>

    <x-table sticky :label="__('reports.salah_attendance_report')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('reports.date') }}</th>
                <th scope="col">{{ __('reports.employee_name') }}</th>
                <th scope="col">{{ __('reports.prayer') }}</th>
                <th scope="col">{{ __('reports.jamaat') }}</th>
                <th scope="col">{{ __('reports.attendance_status') }}</th>
                <th scope="col">{{ __('reports.remarks') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($attendance as $record)
                <tr>
                    <td class="col-num" data-label="#">{{ $attendance->firstItem() + $loop->index }}</td>
                    <td data-label="{{ __('reports.date') }}" class="col-fit tabular">{{ $record->attendance_date->format('d M Y') }}</td>
                    <td data-label="{{ __('reports.employee_name') }}" class="fw-semibold text-strong">{{ $record->employee?->employee_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.prayer') }}">{{ $record->prayer?->prayer_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.jamaat') }}">{{ $record->jamaat?->jamaat_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.attendance_status') }}">
                        @if ($record->isPresent())
                            <span class="badge-soft badge-soft-success">{{ __('reports.present') }}</span>
                        @else
                            <span class="badge-soft badge-soft-danger">{{ $record->attendanceReason?->reason_name ?? '—' }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('reports.remarks') }}">{{ $record->remarks ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state icon="bi-funnel"
                                       :title="$activeFilters ? __('ui.no_results_title') : __('ui.report_ready_title')"
                                       :text="$activeFilters ? __('ui.no_results_text') : __('ui.report_ready_text')">
                            @if ($activeFilters)
                                <a href="{{ route('reports.salah-attendance') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            @endif
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$attendance" />
</x-card>
@endsection
