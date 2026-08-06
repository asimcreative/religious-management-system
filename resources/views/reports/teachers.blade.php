@extends('layouts.app')

@section('title', __('reports.teacher_report'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.reports') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.teacher_report') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];
    if (filled($filters['search'] ?? null))    { $activeFilters += $chip('search', __('ui.search').': '.$filters['search']); }
    if (filled($filters['branch_id'] ?? null)) { $activeFilters += $chip('branch_id', __('reports.branch').': '.($branches[$filters['branch_id']] ?? '')); }
    if (filled($filters['status'] ?? null)) {
        $case = App\Enums\Status::tryFrom((int) $filters['status']);
        if ($case) { $activeFilters += $chip('status', __('reports.status').': '.$case->label()); }
    }
@endphp

<x-page-header :title="__('reports.teacher_report')"
               :subtitle="__('reports.teacher_report_desc')"
               icon="bi-mortarboard"
               :badge="number_format($teachers->total())">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>{{ __('ui.print') }}</span>
        </button>

        <a href="{{ route('reports.export.teachers', request()->query()) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
            <span>{{ __('ui.download_excel') }}</span>
        </a>

        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('reports.back_to_reports') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="__('reports.teacher_report')"
                :subtitle="__('reports.total_records').': '.number_format($teachers->total())"
                :filters="[
                    __('reports.branch') => ($filters['branch_id'] ?? null) ? ($branches[$filters['branch_id']] ?? null) : null,
                    __('ui.search') => $filters['search'] ?? null,
                ]" />

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('reports.teachers')">
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

        <div class="field field--sm">
            <label for="status" class="form-label">{{ __('reports.status') }}</label>
            <select name="status" id="status" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <div class="table-toolbar">
        <span>{{ __('reports.total_records') }}: <strong class="text-strong tabular">{{ number_format($teachers->total()) }}</strong></span>
        <span class="table-toolbar__actions fs-xs text-subtle">{{ __('ui.generated_on', ['datetime' => now()->format('d M Y, H:i')]) }}</span>
    </div>

    <x-table sticky :label="__('reports.teacher_report')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('reports.teacher_code') }}</th>
                <th scope="col">{{ __('reports.teacher_name') }}</th>
                <th scope="col">{{ __('reports.assigned_branches') }}</th>
                <th scope="col" class="col-fit">{{ __('reports.total_classes') }}</th>
                <th scope="col" class="col-fit">{{ __('reports.active_classes') }}</th>
                <th scope="col">{{ __('reports.status') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($teachers as $teacher)
                <tr>
                    <td class="col-num" data-label="#">{{ $teachers->firstItem() + $loop->index }}</td>
                    <td data-label="{{ __('reports.teacher_code') }}"><span class="code-cell">{{ $teacher->teacher_code }}</span></td>
                    <td data-label="{{ __('reports.teacher_name') }}" class="fw-semibold text-strong">{{ $teacher->getEmployeeName() }}</td>
                    <td data-label="{{ __('reports.assigned_branches') }}">
                        @forelse ($teacher->branches as $branch)
                            <span class="chip"><span>{{ $branch->branch_name }}</span></span>
                        @empty
                            <span class="dash">—</span>
                        @endforelse
                    </td>
                    <td data-label="{{ __('reports.total_classes') }}" class="col-fit tabular">{{ $teacher->quran_classes_count }}</td>
                    <td data-label="{{ __('reports.active_classes') }}" class="col-fit tabular">{{ $teacher->active_classes_count }}</td>
                    <td data-label="{{ __('reports.status') }}"><x-status-badge :status="$teacher->status" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state icon="bi-funnel"
                                       :title="$activeFilters ? __('ui.no_results_title') : __('ui.report_ready_title')"
                                       :text="$activeFilters ? __('ui.no_results_text') : __('ui.report_ready_text')">
                            @if ($activeFilters)
                                <a href="{{ route('reports.teachers') }}" class="btn btn-outline-secondary btn-sm">
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

    <x-table-footer :paginator="$teachers" />
</x-card>
@endsection
