@extends('layouts.app')

@section('title', __('salah_attendance.attendance_history'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('salah_attendance.salah_attendance') }}</li>
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
    if (filled(request('jamaat_id')) && isset($jamaats[request('jamaat_id')])) {
        $activeFilters += $chip('jamaat_id', __('salah_attendance.jamaat').': '.$jamaats[request('jamaat_id')]);
    }
    if (filled(request('date_from'))) {
        $activeFilters += $chip('date_from', __('reports.date_from').': '.request('date_from'));
    }
    if (filled(request('date_to'))) {
        $activeFilters += $chip('date_to', __('reports.date_to').': '.request('date_to'));
    }
@endphp

<x-page-header :title="__('salah_attendance.attendance_history')"
               :subtitle="__('salah_attendance.subtitle')"
               icon="bi-moon-stars"
               :badge="number_format($attendance->total())">
    <x-slot:actions>
        {{-- Add / Import / Export / Sample / Print — the same toolbar on every
             module, gated by this module's own permissions. --}}
        <x-data-toolbar resource="salah-attendance"
                        :create-route="route('salah-attendance.create')"
                        :create-model="App\Models\SalahAttendance::class"
                        :create-label="__('salah_attendance.mark_attendance')"
                        :filters="request()->query()" />
    </x-slot:actions>
</x-page-header>

<x-print-header :title="__('salah_attendance.attendance_history')"
                :filters="[
                    __('salah_attendance.jamaat') => request('jamaat_id') ? ($jamaats[request('jamaat_id')] ?? null) : null,
                    __('reports.date_from') => request('date_from'),
                    __('reports.date_to') => request('date_to'),
                ]" />

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('salah-attendance.index')">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('salah_attendance.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>

        <div class="field field--lg">
            <label for="filter_jamaat" class="form-label">{{ __('salah_attendance.jamaat') }}</label>
            <select name="jamaat_id" id="filter_jamaat" class="form-select form-select-sm">
                <option value="">{{ __('salah_attendance.all_jamaats') }}</option>
                @foreach ($jamaats as $id => $name)
                    <option value="{{ $id }}" @selected(request('jamaat_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--sm">
            <label for="date_from" class="form-label">{{ __('reports.date_from') }}</label>
            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>

        <div class="field field--sm">
            <label for="date_to" class="form-label">{{ __('reports.date_to') }}</label>
            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
    </x-filters>

    <x-table sticky :stack="false" class="table-pinned" :label="__('salah_attendance.attendance_history')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('salah_attendance.employee_name') }}</th>
                <th scope="col">{{ __('salah_attendance.date') }}</th>
                <th scope="col">{{ __('salah_attendance.jamaat') }}</th>
                @foreach ($prayers as $prayer)
                    <th scope="col" class="text-center">{{ $prayer->prayer_name }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($attendance as $i => $row)
                <tr>
                    <td class="col-num">{{ $attendance->firstItem() + $i }}</td>

                    <td>
                        <div class="cell-primary">
                            <x-avatar :name="$row['employee']?->employee_name ?? ''" />
                            <span class="cell-primary__text">
                                <span class="cell-primary__title">{{ $row['employee']?->employee_name ?? '—' }}</span>
                                <span class="cell-primary__sub code-cell">{{ $row['employee']?->employee_code }}</span>
                            </span>
                        </div>
                    </td>

                    <td class="col-fit">
                        <span class="tabular">{{ $row['date']->format('d M Y') }}</span>
                        <span class="d-block fs-xs text-subtle">{{ $row['date']->format('l') }}</span>
                    </td>

                    <td>{{ $row['jamaat']?->jamaat_name ?? '—' }}</td>

                    @foreach ($prayers as $prayer)
                        @php($rec = $row['prayers']->get($prayer->id))
                        <td class="text-center">
                            @if ($rec === null)
                                <span class="dash" title="{{ __('salah_attendance.not_recorded') }}">—</span>
                            @elseif ($rec->attendance_reason_id === null)
                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                                <span class="visually-hidden">{{ __('salah_attendance.present') }}</span>
                            @else
                                <span class="badge-soft badge-soft--plain"
                                      style="background-color: {{ $rec->attendanceReason?->color ?? '#64748B' }}1F;
                                             color: {{ $rec->attendanceReason?->color ?? '#64748B' }};">
                                    {{ $rec->attendanceReason?->reason_name ?? '—' }}
                                </span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 4 + $prayers->count() }}">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search" :title="__('ui.no_results_title')" :text="__('ui.no_results_text')">
                                <a href="{{ route('salah-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-moon-stars"
                                           :title="__('salah_attendance.empty_title')"
                                           :text="__('salah_attendance.empty_text')">
                                @can('create', App\Models\SalahAttendance::class)
                                    <a href="{{ route('salah-attendance.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                        <span>{{ __('salah_attendance.mark_attendance') }}</span>
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
