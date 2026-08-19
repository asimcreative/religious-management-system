@extends('layouts.app')

@section('title', __('reports.jamaat_taleem_report'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('reports.reports') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('reports.jamaat_taleem_report') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];
    if (filled($filters['jamaat_id'] ?? null)) { $activeFilters += $chip('jamaat_id', __('reports.jamaat').': '.($jamaats[$filters['jamaat_id']] ?? '')); }
    if (filled($filters['date_from'] ?? null)) { $activeFilters += $chip('date_from', __('reports.date_from').': '.$filters['date_from']); }
    if (filled($filters['date_to'] ?? null))   { $activeFilters += $chip('date_to', __('reports.date_to').': '.$filters['date_to']); }

    $rate = (int) ($summary['percentage'] ?? 0);
    $rateTone = $rate >= 85 ? 'success' : ($rate >= 60 ? 'warning' : 'danger');
@endphp

<x-page-header :title="__('reports.jamaat_taleem_report')"
               :subtitle="__('reports.jamaat_taleem_report_desc')"
               icon="bi-book">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>{{ __('ui.print') }}</span>
        </button>

        <a href="{{ route('reports.export.jamaat-taleem', request()->query()) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
            <span>{{ __('ui.download_excel') }}</span>
        </a>

        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('reports.back_to_reports') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="__('reports.jamaat_taleem_report')"
                :filters="[
                    __('reports.jamaat') => ($filters['jamaat_id'] ?? null) ? ($jamaats[$filters['jamaat_id']] ?? null) : null,
                    __('reports.date_from') => $filters['date_from'] ?? null,
                    __('reports.date_to') => $filters['date_to'] ?? null,
                ]" />

{{-- ── Executive summary ───────────────────────────────────────────────── --}}
<div class="report-summary mb-4">
    <x-stat-card :label="__('reports.total')" :value="number_format($summary['total'])" icon="bi-list-check" tone="neutral" />
    <x-stat-card :label="__('reports.taleem_held')" :value="number_format($summary['held'])" icon="bi-check-circle" tone="success" />
    <x-stat-card :label="__('reports.taleem_not_held')" :value="number_format($summary['not_held'])" icon="bi-x-circle" tone="danger" />
    <x-stat-card :label="__('reports.taleem_rate')" :value="$rate.'%'" icon="bi-percent" :tone="$rateTone">
        <x-slot:meta>
            <span class="progress w-100">
                <span class="progress-bar bg-{{ $rateTone === 'danger' ? 'danger' : ($rateTone === 'warning' ? 'warning' : 'success') }}"
                      style="width: {{ $rate }}%"></span>
            </span>
        </x-slot:meta>
    </x-stat-card>
</div>

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('reports.jamaat-taleem')">
        <div class="field field--md">
            <label for="jamaat_id" class="form-label">{{ __('reports.jamaat') }}</label>
            <select name="jamaat_id" id="jamaat_id" class="form-select form-select-sm">
                <option value="">{{ __('reports.all_jamaats') }}</option>
                @foreach ($jamaats as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['jamaat_id'] ?? '') == $id)>{{ $name }}</option>
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
        <span>{{ __('reports.total_records') }}: <strong class="text-strong tabular">{{ number_format($taleem->total()) }}</strong></span>
        <span class="table-toolbar__actions fs-xs text-subtle">{{ __('ui.generated_on', ['datetime' => now()->format('d M Y, H:i')]) }}</span>
    </div>

    <x-table sticky :label="__('reports.jamaat_taleem_report')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('reports.date') }}</th>
                <th scope="col">{{ __('reports.jamaat') }}</th>
                <th scope="col">{{ __('reports.leader') }}</th>
                <th scope="col">{{ __('reports.taleem_status') }}</th>
                <th scope="col">{{ __('reports.remarks') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($taleem as $record)
                <tr>
                    <td class="col-num" data-label="#">{{ $taleem->firstItem() + $loop->index }}</td>
                    <td data-label="{{ __('reports.date') }}" class="col-fit tabular">{{ $record->attendance_date->format('d M Y') }}</td>
                    <td data-label="{{ __('reports.jamaat') }}" class="fw-semibold text-strong">{{ $record->jamaat?->jamaat_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.leader') }}">{{ $record->leader?->employee_name ?? '—' }}</td>
                    <td data-label="{{ __('reports.taleem_status') }}">
                        @if ($record->isHeld())
                            <span class="badge-soft badge-soft-success">{{ __('reports.taleem_held') }}</span>
                        @else
                            <span class="badge-soft badge-soft-danger">{{ $record->attendanceReason?->reason_name ?? '—' }}</span>
                        @endif
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
                                <a href="{{ route('reports.jamaat-taleem') }}" class="btn btn-outline-secondary btn-sm">
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

    <x-table-footer :paginator="$taleem" />
</x-card>
@endsection
