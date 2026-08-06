@extends('layouts.app')

@section('title', __('quran_attendance.quran_attendance'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_attendance.quran_attendance') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];

    if (filled(request('class_id')) && isset($classes[request('class_id')])) {
        $activeFilters += $chip('class_id', __('quran_attendance.class').': '.$classes[request('class_id')]);
    }
    if (filled(request('teacher_id')) && isset($teachers[request('teacher_id')])) {
        $activeFilters += $chip('teacher_id', __('quran_attendance.teacher').': '.$teachers[request('teacher_id')]);
    }
    if (filled(request('date_from'))) {
        $activeFilters += $chip('date_from', __('quran_attendance.date_from').': '.request('date_from'));
    }
    if (filled(request('date_to'))) {
        $activeFilters += $chip('date_to', __('quran_attendance.date_to').': '.request('date_to'));
    }
@endphp

<x-page-header :title="__('quran_attendance.quran_attendance')"
               :subtitle="__('quran_attendance.subtitle')"
               icon="bi-clipboard-check"
               :badge="number_format($attendance->total())">
    <x-slot:actions>
        {{-- Add / Import / Export / Sample / Print — the same toolbar on every
             module, gated by this module's own permissions. --}}
        <x-data-toolbar resource="quran-attendance"
                        :create-route="route('quran-attendance.create')"
                        :create-model="App\Models\QuranAttendance::class"
                        :create-label="__('quran_attendance.mark_attendance')"
                        :filters="request()->query()" />
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('quran-attendance.index')">
        <div class="field field--grow">
            <label for="filter_class" class="form-label">{{ __('quran_attendance.class') }}</label>
            <select name="class_id" id="filter_class" class="form-select form-select-sm">
                <option value="">{{ __('quran_attendance.all_classes') }}</option>
                @foreach ($classes as $id => $name)
                    <option value="{{ $id }}" @selected(request('class_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--grow">
            <label for="filter_teacher" class="form-label">{{ __('quran_attendance.teacher') }}</label>
            <select name="teacher_id" id="filter_teacher" class="form-select form-select-sm">
                <option value="">{{ __('quran_attendance.all_teachers') }}</option>
                @foreach ($teachers as $id => $name)
                    <option value="{{ $id }}" @selected(request('teacher_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--sm">
            <label for="date_from" class="form-label">{{ __('quran_attendance.date_from') }}</label>
            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>

        <div class="field field--sm">
            <label for="date_to" class="form-label">{{ __('quran_attendance.date_to') }}</label>
            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
    </x-filters>

    <x-table sticky :label="__('quran_attendance.quran_attendance')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('quran_attendance.date') }}</th>
                <th scope="col">{{ __('quran_attendance.employee') }}</th>
                <th scope="col">{{ __('quran_attendance.class') }}</th>
                <th scope="col">{{ __('quran_attendance.status') }}</th>
                <th scope="col">{{ __('quran_attendance.teacher') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($attendance as $record)
                <tr>
                    <td class="col-num" data-label="#">{{ $attendance->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('quran_attendance.date') }}" class="col-fit">
                        <span class="tabular">{{ $record->attendance_date->format('d M Y') }}</span>
                        <span class="d-block fs-xs text-subtle">{{ $record->attendance_date->format('l') }}</span>
                    </td>

                    <td data-label="{{ __('quran_attendance.employee') }}">
                        <div class="cell-primary">
                            <x-avatar :name="$record->employee?->employee_name ?? ''" />
                            <span class="cell-primary__text">
                                <span class="cell-primary__title">{{ $record->employee?->employee_name ?? '—' }}</span>
                                <span class="cell-primary__sub code-cell">{{ $record->employee?->employee_code }}</span>
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('quran_attendance.class') }}">
                        {{ $record->quranClass?->class_name ?? '—' }}
                    </td>

                    <td data-label="{{ __('quran_attendance.status') }}">
                        @if ($record->attendanceReason)
                            {{-- The reason colour is configured in Master Data; it tints the
                                 pill while text stays on a readable neutral surface. --}}
                            <span class="badge-soft"
                                  style="background-color: {{ $record->attendanceReason->color ?? '#64748B' }}1F;
                                         color: {{ $record->attendanceReason->color ?? '#64748B' }};">
                                {{ $record->attendanceReason->reason_name }}
                            </span>
                        @else
                            <span class="badge-soft badge-soft-success">{{ __('quran_attendance.present') }}</span>
                        @endif
                    </td>

                    <td data-label="{{ __('quran_attendance.teacher') }}">
                        {{ $record->teacher?->employee?->employee_name ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search" :title="__('ui.no_results_title')" :text="__('ui.no_results_text')">
                                <a href="{{ route('quran-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-clipboard-check"
                                           :title="__('quran_attendance.empty_title')"
                                           :text="__('quran_attendance.empty_text')">
                                @can('create', App\Models\QuranAttendance::class)
                                    <a href="{{ route('quran-attendance.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-clipboard-plus" aria-hidden="true"></i>
                                        <span>{{ __('quran_attendance.mark_attendance') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$attendance" />
</x-card>
@endsection
