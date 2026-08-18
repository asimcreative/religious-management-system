@extends('layouts.app')

@section('title', __('reports.quran_teacher_attendance_report'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.reports') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.quran_teacher_attendance_report') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];
    if (filled($filters['class_id'] ?? null))   { $activeFilters += $chip('class_id', __('reports.class').': '.($classes[$filters['class_id']] ?? '')); }
    if (filled($filters['teacher_id'] ?? null)) { $activeFilters += $chip('teacher_id', __('reports.teacher').': '.($teachers[$filters['teacher_id']] ?? '')); }
    if (filled($filters['date_from'] ?? null))  { $activeFilters += $chip('date_from', __('reports.date_from').': '.$filters['date_from']); }
    if (filled($filters['date_to'] ?? null))    { $activeFilters += $chip('date_to', __('reports.date_to').': '.$filters['date_to']); }

    $teachersAffected = $monthly->pluck('teacher_id')->unique()->count();
@endphp

<x-page-header :title="__('reports.quran_teacher_attendance_report')"
               :subtitle="__('reports.quran_teacher_attendance_report_desc')"
               icon="bi-person-x">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>{{ __('ui.print') }}</span>
        </button>

        <a href="{{ route('reports.export.quran-teacher-attendance', request()->query()) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
            <span>{{ __('ui.download_excel') }}</span>
        </a>

        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('reports.back_to_reports') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="__('reports.quran_teacher_attendance_report')"
                :filters="[
                    __('reports.class') => ($filters['class_id'] ?? null) ? ($classes[$filters['class_id']] ?? null) : null,
                    __('reports.teacher') => ($filters['teacher_id'] ?? null) ? ($teachers[$filters['teacher_id']] ?? null) : null,
                    __('reports.date_from') => $filters['date_from'] ?? null,
                    __('reports.date_to') => $filters['date_to'] ?? null,
                ]" />

{{-- ── Executive summary ───────────────────────────────────────────────── --}}
<div class="report-summary mb-4">
    <x-stat-card :label="__('reports.teacher_absent_days')" :value="number_format($attendance->total())" icon="bi-calendar-x" tone="danger" />
    <x-stat-card :label="__('reports.teachers_affected')" :value="number_format($teachersAffected)" icon="bi-people" tone="neutral" />
</div>

{{-- ── By teacher, by month ────────────────────────────────────────────── --}}
@if ($monthly->isNotEmpty())
    <x-card class="mb-4">
        <x-slot:title>{{ __('reports.absences_by_teacher_month') }}</x-slot:title>

        <x-table :label="__('reports.absences_by_teacher_month')">
            <thead>
                <tr>
                    <th scope="col">{{ __('reports.teacher') }}</th>
                    <th scope="col">{{ __('reports.month') }}</th>
                    <th scope="col" class="col-fit">{{ __('reports.teacher_absent_days') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($monthly as $row)
                    <tr>
                        <td data-label="{{ __('reports.teacher') }}" class="fw-semibold text-strong">{{ $row->employee_name }}</td>
                        <td data-label="{{ __('reports.month') }}" class="tabular">{{ $row->month }}</td>
                        <td data-label="{{ __('reports.teacher_absent_days') }}" class="col-fit tabular">{{ $row->absent_days }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
@endif

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('reports.quran-teacher-attendance')">
        <div class="field field--md">
            <label for="class_id" class="form-label">{{ __('reports.class') }}</label>
            <select name="class_id" id="class_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_classes') }}</option>
                @foreach ($classes as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['class_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="teacher_id" class="form-label">{{ __('reports.teacher') }}</label>
            <select name="teacher_id" id="teacher_id" class="form-select form-select-sm" data-searchable-select>
                <option value="">{{ __('reports.all_teachers') }}</option>
                @foreach ($teachers as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['teacher_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--sm">
            <label for="date_from" class="form-label">{{ __('reports.date_from') }}</label>
            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
        </div>

        <div class="field field--sm">
            <label for="date_to" class="form-label">{{ __('reports.date_to') }}</label>
            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
        </div>
    </x-filters>

    <div class="table-toolbar">
        <span>{{ __('reports.total_records') }}: <strong class="text-strong tabular">{{ number_format($attendance->total()) }}</strong></span>
        <span class="table-toolbar__actions fs-xs text-subtle">{{ __('ui.generated_on', ['datetime' => now()->format('d M Y, H:i')]) }}</span>
    </div>

    <x-table sticky :label="__('reports.quran_teacher_attendance_report')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('reports.date') }}</th>
                <th scope="col">{{ __('reports.teacher') }}</th>
                <th scope="col">{{ __('reports.class') }}</th>
                <th scope="col">{{ __('reports.reason') }}</th>
                <th scope="col">{{ __('reports.remarks') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($attendance as $record)
                <tr>
                    <td class="col-num" data-label="#">{{ $attendance->firstItem() + $loop->index }}</td>
                    <td data-label="{{ __('reports.date') }}" class="col-fit tabular">{{ $record->attendance_date->format('d M Y') }}</td>
                    <td data-label="{{ __('reports.teacher') }}" class="fw-semibold text-strong">{{ $record->teacher?->getEmployeeName() ?? '—' }}</td>
                    <td data-label="{{ __('reports.class') }}">{{ $record->quranClass?->class_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.reason') }}">
                        <span class="badge-soft" style="background-color: {{ $record->attendanceReason?->color ?? '#64748B' }}1F; color: {{ $record->attendanceReason?->color ?? '#64748B' }};">
                            {{ $record->attendanceReason?->reason_name ?? '—' }}
                        </span>
                    </td>
                    <td data-label="{{ __('reports.remarks') }}">{{ $record->remarks ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-empty-state icon="bi-funnel"
                                       :title="$activeFilters ? __('ui.no_results_title') : __('ui.report_ready_title')"
                                       :text="$activeFilters ? __('ui.no_results_text') : __('ui.report_ready_text')">
                            @if ($activeFilters)
                                <a href="{{ route('reports.quran-teacher-attendance') }}" class="btn btn-outline-secondary btn-sm">
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
