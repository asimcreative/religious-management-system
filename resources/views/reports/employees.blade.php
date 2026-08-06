@extends('layouts.app')

@section('title', __('reports.employee_report'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.reports') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.employee_report') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];
    if (filled($filters['search'] ?? null))          { $activeFilters += $chip('search', __('ui.search').': '.$filters['search']); }
    if (filled($filters['branch_id'] ?? null))       { $activeFilters += $chip('branch_id', __('reports.branch').': '.($branches[$filters['branch_id']] ?? '')); }
    if (filled($filters['department_id'] ?? null))   { $activeFilters += $chip('department_id', __('reports.department').': '.($departments[$filters['department_id']] ?? '')); }
    if (filled($filters['designation_id'] ?? null))  { $activeFilters += $chip('designation_id', __('reports.designation').': '.($designations[$filters['designation_id']] ?? '')); }
    if (filled($filters['employment_status'] ?? null)) {
        $case = App\Enums\Status::tryFrom((int) $filters['employment_status']);
        if ($case) { $activeFilters += $chip('employment_status', __('reports.status').': '.$case->label()); }
    }
@endphp

<x-page-header :title="__('reports.employee_report')"
               :subtitle="__('reports.employee_report_desc')"
               icon="bi-people"
               :badge="number_format($employees->total())">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>{{ __('ui.print') }}</span>
        </button>

        <a href="{{ route('reports.export.employees', request()->query()) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
            <span>{{ __('ui.download_excel') }}</span>
        </a>

        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('reports.back_to_reports') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="__('reports.employee_report')"
                :subtitle="__('reports.total_records').': '.number_format($employees->total())"
                :filters="[
                    __('reports.branch') => ($filters['branch_id'] ?? null) ? ($branches[$filters['branch_id']] ?? null) : null,
                    __('reports.department') => ($filters['department_id'] ?? null) ? ($departments[$filters['department_id']] ?? null) : null,
                    __('reports.designation') => ($filters['designation_id'] ?? null) ? ($designations[$filters['designation_id']] ?? null) : null,
                    __('ui.search') => $filters['search'] ?? null,
                ]" />

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('reports.employees')">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('reports.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="field field--md">
            <label for="branch_id" class="form-label">{{ __('reports.branch') }}</label>
            <select name="branch_id" id="branch_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_branches') }}</option>
                @foreach ($branches as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['branch_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="department_id" class="form-label">{{ __('reports.department') }}</label>
            <select name="department_id" id="department_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_departments') }}</option>
                @foreach ($departments as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['department_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="designation_id" class="form-label">{{ __('reports.designation') }}</label>
            <select name="designation_id" id="designation_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_designations') }}</option>
                @foreach ($designations as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['designation_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--sm">
            <label for="employment_status" class="form-label">{{ __('reports.status') }}</label>
            <select name="employment_status" id="employment_status" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['employment_status'] ?? null) === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <div class="table-toolbar">
        <span>{{ __('reports.total_records') }}: <strong class="text-strong tabular">{{ number_format($employees->total()) }}</strong></span>
        <span class="table-toolbar__actions fs-xs text-subtle">{{ __('ui.generated_on', ['datetime' => now()->format('d M Y, H:i')]) }}</span>
    </div>

    <x-table sticky :label="__('reports.employee_report')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('reports.employee_code') }}</th>
                <th scope="col">{{ __('reports.employee_name') }}</th>
                <th scope="col">{{ __('reports.branch') }}</th>
                <th scope="col">{{ __('reports.department') }}</th>
                <th scope="col">{{ __('reports.designation') }}</th>
                <th scope="col">{{ __('reports.mobile') }}</th>
                <th scope="col">{{ __('reports.status') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($employees as $emp)
                <tr>
                    <td class="col-num" data-label="#">{{ $employees->firstItem() + $loop->index }}</td>
                    <td data-label="{{ __('reports.employee_code') }}"><span class="code-cell">{{ $emp->employee_code }}</span></td>
                    <td data-label="{{ __('reports.employee_name') }}" class="fw-semibold text-strong">{{ $emp->employee_name }}</td>
                    <td data-label="{{ __('reports.branch') }}">{{ $emp->branch?->branch_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.department') }}">{{ $emp->department?->department_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.designation') }}">{{ $emp->designation?->designation_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.mobile') }}" class="mono">{{ $emp->mobile ?? '—' }}</td>
                    <td data-label="{{ __('reports.status') }}"><x-status-badge :status="$emp->employment_status" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <x-empty-state icon="bi-funnel"
                                       :title="$activeFilters ? __('ui.no_results_title') : __('ui.report_ready_title')"
                                       :text="$activeFilters ? __('ui.no_results_text') : __('ui.report_ready_text')">
                            @if ($activeFilters)
                                <a href="{{ route('reports.employees') }}" class="btn btn-outline-secondary btn-sm">
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

    <x-table-footer :paginator="$employees" />
</x-card>
@endsection
