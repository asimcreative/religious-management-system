@extends('layouts.app')

@section('title', __('reports.quran_progress_report'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.reports') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.quran_progress_report') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];
    if (filled($filters['search'] ?? null))               { $activeFilters += $chip('search', __('ui.search').': '.$filters['search']); }
    if (filled($filters['quran_department_id'] ?? null))  { $activeFilters += $chip('quran_department_id', __('reports.quran_department').': '.($quranDepartments[$filters['quran_department_id']] ?? '')); }
    if (filled($filters['quran_status_id'] ?? null))      { $activeFilters += $chip('quran_status_id', __('reports.quran_status').': '.($quranStatuses[$filters['quran_status_id']] ?? '')); }
    if (filled($filters['teacher_id'] ?? null))           { $activeFilters += $chip('teacher_id', __('reports.teacher').': '.($teachers[$filters['teacher_id']] ?? '')); }
@endphp

<x-page-header :title="__('reports.quran_progress_report')"
               :subtitle="__('reports.quran_progress_report_desc')"
               icon="bi-graph-up-arrow"
               :badge="number_format($progress->total())">
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

<x-print-header :title="__('reports.quran_progress_report')"
                :subtitle="__('reports.total_records').': '.number_format($progress->total())"
                :filters="[
                    __('reports.quran_department') => ($filters['quran_department_id'] ?? null) ? ($quranDepartments[$filters['quran_department_id']] ?? null) : null,
                    __('reports.quran_status') => ($filters['quran_status_id'] ?? null) ? ($quranStatuses[$filters['quran_status_id']] ?? null) : null,
                    __('reports.teacher') => ($filters['teacher_id'] ?? null) ? ($teachers[$filters['teacher_id']] ?? null) : null,
                ]" />

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('reports.quran-progress')">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('reports.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}"
                       data-searchable-select-freeform data-employee-options="{{ json_encode($employeeOptions) }}">
            </div>
        </div>

        <div class="field field--lg">
            <label for="quran_department_id" class="form-label">{{ __('reports.quran_department') }}</label>
            <select name="quran_department_id" id="quran_department_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_departments_quran') }}</option>
                @foreach ($quranDepartments as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['quran_department_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="quran_status_id" class="form-label">{{ __('reports.quran_status') }}</label>
            <select name="quran_status_id" id="quran_status_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_statuses_quran') }}</option>
                @foreach ($quranStatuses as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['quran_status_id'] ?? '') == $id)>{{ $name }}</option>
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
    </x-filters>

    <div class="table-toolbar">
        <span>{{ __('reports.total_records') }}: <strong class="text-strong tabular">{{ number_format($progress->total()) }}</strong></span>
        <span class="table-toolbar__actions fs-xs text-subtle">{{ __('ui.generated_on', ['datetime' => now()->format('d M Y, H:i')]) }}</span>
    </div>

    <x-table sticky :label="__('reports.quran_progress_report')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('reports.employee') }}</th>
                <th scope="col">{{ __('reports.teacher') }}</th>
                <th scope="col">{{ __('reports.quran_department') }}</th>
                <th scope="col">{{ __('reports.quran_status') }}</th>
                <th scope="col">{{ __('reports.current_lesson') }}</th>
                <th scope="col" style="min-width: 10rem;">{{ __('reports.completion') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($progress as $p)
                @php
                    $pct = (float) $p->completion_percentage;
                    $tone = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                @endphp
                <tr>
                    <td class="col-num" data-label="#">{{ $progress->firstItem() + $loop->index }}</td>
                    <td data-label="{{ __('reports.employee') }}" class="fw-semibold text-strong">{{ $p->employee?->employee_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.teacher') }}">{{ $p->teacher?->getEmployeeName() ?? '—' }}</td>
                    <td data-label="{{ __('reports.quran_department') }}">{{ $p->quranDepartment?->department_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.quran_status') }}">
                        @if ($p->quranStatus)
                            <span class="badge-soft"
                                  style="background-color: {{ $p->quranStatus->color ?? '#64748B' }}1F;
                                         color: {{ $p->quranStatus->color ?? '#64748B' }};">
                                {{ $p->quranStatus->status_name }}
                            </span>
                        @else
                            <span class="dash">—</span>
                        @endif
                    </td>
                    <td data-label="{{ __('reports.current_lesson') }}">{{ $p->current_lesson ?? '—' }}</td>
                    <td data-label="{{ __('reports.completion') }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="progress flex-grow-1 meter-track" role="img" aria-label="{{ number_format($pct, 0) }}%">
                                <span class="progress-bar bg-{{ $tone }}" style="width: {{ min(100, $pct) }}%"></span>
                            </span>
                            <span class="tabular fw-semibold fs-sm metric-value">
                                {{ number_format($pct, 0) }}%
                            </span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state icon="bi-funnel"
                                       :title="$activeFilters ? __('ui.no_results_title') : __('ui.report_ready_title')"
                                       :text="$activeFilters ? __('ui.no_results_text') : __('ui.report_ready_text')">
                            @if ($activeFilters)
                                <a href="{{ route('reports.quran-progress') }}" class="btn btn-outline-secondary btn-sm">
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

    <x-table-footer :paginator="$progress" />
</x-card>
@endsection
