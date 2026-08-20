@extends('layouts.app')

@section('title', __('quran_progress.quran_progress'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_progress.quran_progress') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];

    if (filled(request('search'))) {
        $activeFilters += $chip('search', __('ui.search').': '.request('search'));
    }
    if (filled(request('quran_department_id')) && isset($quranDepartments[request('quran_department_id')])) {
        $activeFilters += $chip('quran_department_id', __('quran_progress.department').': '.$quranDepartments[request('quran_department_id')]);
    }
    if (filled(request('quran_status_id')) && isset($quranStatuses[request('quran_status_id')])) {
        $activeFilters += $chip('quran_status_id', __('quran_progress.quran_status').': '.$quranStatuses[request('quran_status_id')]);
    }
    if (filled(request('teacher_id')) && isset($teachers[request('teacher_id')])) {
        $activeFilters += $chip('teacher_id', __('quran_progress.teacher').': '.$teachers[request('teacher_id')]);
    }
@endphp

<x-page-header :title="__('quran_progress.quran_progress')"
               :subtitle="__('quran_progress.subtitle')"
               icon="bi-graph-up-arrow"
               :badge="number_format($progress->total())">
    <x-slot:actions>
        {{-- Add / Import / Export / Sample / Print — the same toolbar on every
             module, gated by this module's own permissions. --}}
        <x-data-toolbar resource="quran-progress"
                        :create-route="route('quran-progress.create')"
                        :create-model="App\Models\QuranProgress::class"
                        :create-label="__('quran_progress.update_progress')"
                        :filters="request()->query()" />
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('quran-progress.index')">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('quran_progress.search_placeholder') }}" value="{{ request('search') }}"
                       data-searchable-select-freeform data-employee-options="{{ json_encode($employeeOptions) }}">
            </div>
        </div>

        <div class="field field--md">
            <label for="filter_department" class="form-label">{{ __('quran_progress.department') }}</label>
            <select name="quran_department_id" id="filter_department" class="form-select form-select-sm">
                <option value="">{{ __('quran_progress.all_departments') }}</option>
                @foreach ($quranDepartments as $id => $name)
                    <option value="{{ $id }}" @selected(request('quran_department_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="filter_qstatus" class="form-label">{{ __('quran_progress.quran_status') }}</label>
            <select name="quran_status_id" id="filter_qstatus" class="form-select form-select-sm">
                <option value="">{{ __('quran_progress.all_statuses') }}</option>
                @foreach ($quranStatuses as $id => $name)
                    <option value="{{ $id }}" @selected(request('quran_status_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="filter_teacher" class="form-label">{{ __('quran_progress.teacher') }}</label>
            <select name="teacher_id" id="filter_teacher" class="form-select form-select-sm" data-searchable-select>
                <option value="">{{ __('quran_progress.all_teachers') }}</option>
                @foreach ($teachers as $id => $name)
                    <option value="{{ $id }}" @selected(request('teacher_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <x-table sticky :label="__('quran_progress.quran_progress')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('quran_progress.employee') }}</th>
                <th scope="col">{{ __('quran_progress.department') }}</th>
                <th scope="col">{{ __('quran_progress.quran_status') }}</th>
                <th scope="col">{{ __('quran_progress.progress_snapshot') }}</th>
                <th scope="col" style="min-width: 9rem;">{{ __('quran_progress.completion') }}</th>
                <th scope="col">{{ __('quran_progress.teacher') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('quran_progress.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($progress as $record)
                @php($pct = (float) $record->completion_percentage)
                <tr>
                    <td class="col-num" data-label="#">{{ $progress->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('quran_progress.employee') }}">
                        <div class="cell-primary">
                            <x-avatar :name="$record->employee?->employee_name ?? ''" />
                            <span class="cell-primary__text">
                                @can('view', $record)
                                    <a href="{{ route('quran-progress.show', $record) }}" class="cell-primary__title">
                                        {{ $record->employee?->employee_name ?? '—' }}
                                    </a>
                                @else
                                    <span class="cell-primary__title">{{ $record->employee?->employee_name ?? '—' }}</span>
                                @endcan
                                <span class="cell-primary__sub code-cell">{{ $record->employee?->employee_code }}</span>
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('quran_progress.department') }}">
                        {{ $record->quranDepartment?->department_name ?? '—' }}
                    </td>

                    <td data-label="{{ __('quran_progress.quran_status') }}">
                        @if ($record->quranStatus)
                            <span class="badge-soft"
                                  style="background-color: {{ $record->quranStatus->color ?? '#64748B' }}1F;
                                         color: {{ $record->quranStatus->color ?? '#64748B' }};">
                                {{ $record->quranStatus->status_name }}
                            </span>
                        @else
                            <span class="dash">—</span>
                        @endif
                    </td>

                    <td data-label="{{ __('quran_progress.progress_snapshot') }}">
                        @php($recordSchema = $record->quranDepartment?->progress_fields_schema ?? [])
                        @if (! empty($recordSchema))
                            @php($firstField = $recordSchema[0])
                            {{ $firstField['label'] }}: {{ $record->field_values[$firstField['key']] ?? '—' }}
                        @else
                            {{ $record->current_lesson ?? '—' }}
                        @endif
                    </td>

                    <td data-label="{{ __('quran_progress.completion') }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="progress flex-grow-1 meter-track" role="img" aria-label="{{ number_format($pct, 0) }}%">
                                <span class="progress-bar {{ $pct >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $pct }}%"></span>
                            </span>
                            <span class="tabular fw-semibold fs-sm metric-value">
                                {{ number_format($pct, 0) }}%
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('quran_progress.teacher') }}">
                        {{ $record->teacher?->employee?->employee_name ?? '—' }}
                    </td>

                    <td class="col-actions" data-label="{{ __('quran_progress.actions') }}">
                        <div class="table-actions">
                            @can('view', $record)
                                <a href="{{ route('quran-progress.show', $record) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('quran_progress.view') }}"
                                   aria-label="{{ __('quran_progress.view') }} — {{ $record->employee?->employee_name }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                            @endcan
                            @can('update', $record)
                                <a href="{{ route('quran-progress.edit', $record) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('quran_progress.edit') }}"
                                   aria-label="{{ __('quran_progress.edit') }} — {{ $record->employee?->employee_name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search" :title="__('ui.no_results_title')" :text="__('ui.no_results_text')">
                                <a href="{{ route('quran-progress.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-graph-up-arrow"
                                           :title="__('quran_progress.empty_title')"
                                           :text="__('quran_progress.empty_text')">
                                @can('create', App\Models\QuranProgress::class)
                                    <a href="{{ route('quran-progress.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        <span>{{ __('quran_progress.update_progress') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$progress" />
</x-card>

@can('create', App\Models\QuranProgress::class)
    <a href="{{ route('quran-progress.create') }}" class="btn btn-primary btn-fab" aria-label="{{ __('quran_progress.update_progress') }}">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
    </a>
@endcan
@endsection
