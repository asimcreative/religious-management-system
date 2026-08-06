@extends('layouts.app')

@section('title', __('employees.employees'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('employees.employees') }}</li>
@endsection

@section('content')
@php
    // Chips let the user see and undo exactly what is narrowing the list.
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];

    if (filled(request('search'))) {
        $activeFilters += $chip('search', __('ui.search').': '.request('search'));
    }
    if (filled(request('branch_id')) && isset($branches[request('branch_id')])) {
        $activeFilters += $chip('branch_id', __('employees.branch').': '.$branches[request('branch_id')]);
    }
    if (filled(request('department_id')) && isset($departments[request('department_id')])) {
        $activeFilters += $chip('department_id', __('employees.department').': '.$departments[request('department_id')]);
    }
    if (filled(request('designation_id')) && isset($designations[request('designation_id')])) {
        $activeFilters += $chip('designation_id', __('employees.designation').': '.$designations[request('designation_id')]);
    }
    if (filled(request('employment_status'))) {
        $case = App\Enums\Status::tryFrom((int) request('employment_status'));
        if ($case) {
            $activeFilters += $chip('employment_status', __('employees.status').': '.$case->label());
        }
    }
    if (filled(request('quran_department_id')) && isset($quranDepartments[request('quran_department_id')])) {
        $activeFilters += $chip('quran_department_id', __('employees.quran_department').': '.$quranDepartments[request('quran_department_id')]);
    }
    if (filled(request('quran_status_id')) && isset($quranStatuses[request('quran_status_id')])) {
        $activeFilters += $chip('quran_status_id', __('employees.quran_status').': '.$quranStatuses[request('quran_status_id')]);
    }

    $hasAdvanced = filled(request('quran_department_id')) || filled(request('quran_status_id'));
@endphp

<x-page-header :title="__('employees.employees')"
               :subtitle="__('employees.subtitle')"
               icon="bi-people"
               :badge="number_format($employees->total())">
    <x-slot:actions>
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>{{ __('employees.add_new') }}</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-card flush>
    {{-- ── Filters ──────────────────────────────────────────────────────── --}}
    <x-filters :active="$activeFilters" :reset-url="route('employees.index')">
        <div class="flex-grow-1" style="min-width: 15rem;">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('employees.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>

        <div style="min-width: 10rem;">
            <label for="filter_branch" class="form-label">{{ __('employees.branch') }}</label>
            <select name="branch_id" id="filter_branch" class="form-select form-select-sm">
                <option value="">{{ __('employees.all_branches') }}</option>
                @foreach ($branches as $id => $name)
                    <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 10rem;">
            <label for="filter_department" class="form-label">{{ __('employees.department') }}</label>
            <select name="department_id" id="filter_department" class="form-select form-select-sm">
                <option value="">{{ __('employees.all_departments') }}</option>
                @foreach ($departments as $id => $name)
                    <option value="{{ $id }}" @selected(request('department_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 10rem;">
            <label for="filter_designation" class="form-label">{{ __('employees.designation') }}</label>
            <select name="designation_id" id="filter_designation" class="form-select form-select-sm">
                <option value="">{{ __('employees.all_designations') }}</option>
                @foreach ($designations as $id => $name)
                    <option value="{{ $id }}" @selected(request('designation_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 9rem;">
            <label for="filter_status" class="form-label">{{ __('employees.status') }}</label>
            <select name="employment_status" id="filter_status" class="form-select form-select-sm">
                <option value="">{{ __('employees.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('employment_status') === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Secondary filters stay out of the way until needed --}}
        <div class="w-100">
            <div class="collapse {{ $hasAdvanced ? 'show' : '' }}" id="advancedFilters">
                <div class="d-flex flex-wrap gap-2 pt-2 border-top border-subtle">
                    <div style="min-width: 12rem;">
                        <label for="filter_quran_department" class="form-label">{{ __('employees.quran_department') }}</label>
                        <select name="quran_department_id" id="filter_quran_department" class="form-select form-select-sm">
                            <option value="">{{ __('employees.all_quran_depts') }}</option>
                            @foreach ($quranDepartments as $id => $name)
                                <option value="{{ $id }}" @selected(request('quran_department_id') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 12rem;">
                        <label for="filter_quran_status" class="form-label">{{ __('employees.quran_status') }}</label>
                        <select name="quran_status_id" id="filter_quran_status" class="form-select form-select-sm">
                            <option value="">{{ __('employees.all_quran_statuses') }}</option>
                            @foreach ($quranStatuses as $id => $name)
                                <option value="{{ $id }}" @selected(request('quran_status_id') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-ghost mt-1 px-1"
                    data-bs-toggle="collapse" data-bs-target="#advancedFilters"
                    aria-expanded="{{ $hasAdvanced ? 'true' : 'false' }}" aria-controls="advancedFilters">
                <i class="bi bi-sliders2" aria-hidden="true"></i>
                <span>{{ __('employees.more_filters') }}</span>
            </button>
        </div>
    </x-filters>

    {{-- ── Table ────────────────────────────────────────────────────────── --}}
    <x-table sticky :label="__('employees.employees')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('employees.employee_name') }}</th>
                <th scope="col">{{ __('employees.department') }}</th>
                <th scope="col">{{ __('employees.designation') }}</th>
                <th scope="col">{{ __('employees.branch') }}</th>
                <th scope="col">{{ __('employees.mobile') }}</th>
                <th scope="col">{{ __('employees.status') }}</th>
                <th scope="col" class="col-actions">
                    <span class="visually-hidden">{{ __('employees.actions') }}</span>
                </th>
            </tr>
        </thead>

        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td class="col-num" data-label="#">{{ $employees->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('employees.employee_name') }}">
                        <div class="cell-primary">
                            <x-avatar :name="$employee->employee_name" />
                            <span class="cell-primary__text">
                                @can('view', $employee)
                                    <a href="{{ route('employees.show', $employee) }}" class="cell-primary__title">
                                        {{ $employee->employee_name }}
                                    </a>
                                @else
                                    <span class="cell-primary__title">{{ $employee->employee_name }}</span>
                                @endcan
                                <span class="cell-primary__sub code-cell">{{ $employee->employee_code }}</span>
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('employees.department') }}">
                        {{ $employee->department?->department_name ?? '—' }}
                    </td>

                    <td data-label="{{ __('employees.designation') }}">
                        {{ $employee->designation?->designation_name ?? '—' }}
                    </td>

                    <td data-label="{{ __('employees.branch') }}">
                        {{ $employee->branch?->branch_name ?? '—' }}
                    </td>

                    <td data-label="{{ __('employees.mobile') }}">
                        @if ($employee->mobile)
                            <a href="tel:{{ $employee->mobile }}" class="mono">{{ $employee->mobile }}</a>
                        @else
                            <span class="dash">—</span>
                        @endif
                    </td>

                    <td data-label="{{ __('employees.status') }}">
                        <x-status-badge :status="$employee->employment_status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('employees.actions') }}">
                        <div class="table-actions">
                            @can('view', $employee)
                                <a href="{{ route('employees.show', $employee) }}"
                                   class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('employees.view') }}"
                                   aria-label="{{ __('employees.view') }} — {{ $employee->employee_name }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                            @endcan

                            @can('update', $employee)
                                <a href="{{ route('employees.edit', $employee) }}"
                                   class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('employees.edit') }}"
                                   aria-label="{{ __('employees.edit') }} — {{ $employee->employee_name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan

                            @can('delete', $employee)
                                <x-delete-button
                                    :action="route('employees.destroy', $employee)"
                                    :record="$employee->employee_name"
                                    :title="__('employees.delete')" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search"
                                           :title="__('ui.no_results_title')"
                                           :text="__('ui.no_results_text')">
                                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-people"
                                           :title="__('employees.empty_title')"
                                           :text="__('employees.empty_text')">
                                @can('create', App\Models\Employee::class)
                                    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        <span>{{ __('employees.add_new') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$employees" />
</x-card>

@can('create', App\Models\Employee::class)
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-fab"
       aria-label="{{ __('employees.add_new') }}">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
    </a>
@endcan
@endsection
