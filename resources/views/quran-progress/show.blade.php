@extends('layouts.app')

@section('title', __('quran_progress.progress_detail'))

@php
    $pct = (float) $quranProgress->completion_percentage;
    $studentName = $quranProgress->employee?->employee_name ?? '—';
@endphp

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-progress.index') }}">{{ __('quran_progress.quran_progress') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $studentName }}</li>
@endsection

@section('content')
<x-page-header :title="$studentName"
               :subtitle="__('quran_progress.quran_progress')"
               icon="bi-graph-up-arrow">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">{{ __('ui.print') }}</span>
        </button>

        @can('update', $quranProgress)
            <a href="{{ route('quran-progress.edit', $quranProgress) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>{{ __('quran_progress.edit') }}</span>
            </a>
        @endcan

        <a href="{{ route('quran-progress.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('quran_progress.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="$studentName" :subtitle="__('quran_progress.progress_detail')" />

<div class="row g-3">

    {{-- ── Completion meter ─────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">
        <x-card class="h-100" body-class="text-center">
            <div class="meter mb-3"
                 style="--value: {{ min(100, $pct) }}; --tone: {{ $pct >= 100 ? 'var(--rams-success-text)' : 'var(--rams-primary)' }};"
                 role="img"
                 aria-label="{{ __('quran_progress.completion') }}: {{ number_format($pct, 1) }}%">
                <span class="meter__label">{{ number_format($pct, 0) }}%</span>
            </div>

            <p class="fw-semibold mb-1">{{ __('quran_progress.completion') }}</p>

            @if ($quranProgress->quranStatus)
                <span class="badge-soft"
                      style="background-color: {{ $quranProgress->quranStatus->color ?? '#64748B' }}1F;
                             color: {{ $quranProgress->quranStatus->color ?? '#64748B' }};">
                    {{ $quranProgress->quranStatus->status_name }}
                </span>
            @endif

            <div class="metric-strip mt-3">
                <div class="metric-strip__item">
                    <div class="metric-strip__value">{{ $quranProgress->current_sipara ?? '—' }}</div>
                    <div class="metric-strip__label">{{ __('quran_progress.sipara') }}</div>
                </div>
                <div class="metric-strip__item">
                    <div class="metric-strip__value">{{ $quranProgress->current_page ?? '—' }}</div>
                    <div class="metric-strip__label">{{ __('quran_progress.page') }}</div>
                </div>
            </div>
        </x-card>
    </div>

    {{-- ── Details ──────────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
        <x-card :title="__('quran_progress.current_position')" icon="bi-bookmark" class="h-100">
            <x-detail-list>
                <x-detail-row :label="__('quran_progress.employee')">
                    @if ($quranProgress->employee)
                        @can('view', $quranProgress->employee)
                            <a href="{{ route('employees.show', $quranProgress->employee) }}">{{ $quranProgress->employee->employee_name }}</a>
                        @else
                            {{ $quranProgress->employee->employee_name }}
                        @endcan
                        <span class="code-cell ms-1">({{ $quranProgress->employee->employee_code }})</span>
                    @endif
                </x-detail-row>
                <x-detail-row :label="__('quran_progress.department')" :value="$quranProgress->quranDepartment?->department_name" />
                <x-detail-row :label="__('quran_progress.lesson')" :value="$quranProgress->current_lesson" />
                <x-detail-row :label="__('quran_progress.surah')" :value="$quranProgress->current_surah" />
                <x-detail-row :label="__('quran_progress.completion')">
                    <div class="d-flex align-items-center gap-2">
                        <span class="progress flex-grow-1 w-cap-md">
                            <span class="progress-bar {{ $pct >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ min(100, $pct) }}%"></span>
                        </span>
                        <span class="tabular fw-semibold">{{ number_format($pct, 1) }}%</span>
                    </div>
                </x-detail-row>
                <x-detail-row :label="__('quran_progress.teacher')" :value="$quranProgress->teacher?->employee?->employee_name" />
                <x-detail-row :label="__('quran_progress.remarks')" :value="$quranProgress->remarks" />
                <x-detail-row :label="__('quran_progress.last_updated')">
                    {{ $quranProgress->updater?->name ?? '—' }}
                    <span class="d-block fs-xs text-subtle">{{ $quranProgress->updated_at?->format('d M Y, H:i') }}</span>
                </x-detail-row>
            </x-detail-list>
        </x-card>
    </div>

    {{-- ── History ──────────────────────────────────────────────────────── --}}
    <div class="col-12">
        <x-card :title="__('quran_progress.history')" icon="bi-clock-history" flush>
            <x-slot:actions>
                <span class="badge-soft badge-soft-neutral">{{ $quranProgress->history->count() }}</span>
            </x-slot:actions>

            @if ($quranProgress->history->isNotEmpty())
                <x-table :label="__('quran_progress.history')">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('quran_progress.date') }}</th>
                            <th scope="col">{{ __('quran_progress.department') }}</th>
                            <th scope="col">{{ __('quran_progress.lesson') }}</th>
                            <th scope="col" style="min-width: 9rem;">{{ __('quran_progress.completion') }}</th>
                            <th scope="col">{{ __('quran_progress.updated_by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quranProgress->history as $entry)
                            @php($entryPct = (float) $entry->percentage)
                            <tr>
                                <td data-label="{{ __('quran_progress.date') }}" class="col-fit">
                                    <span class="tabular">{{ $entry->created_at?->format('d M Y') }}</span>
                                </td>
                                <td data-label="{{ __('quran_progress.department') }}">
                                    {{ $entry->quranDepartment?->department_name ?? '—' }}
                                </td>
                                <td data-label="{{ __('quran_progress.lesson') }}">
                                    {{ $entry->lesson ?? '—' }}
                                </td>
                                <td data-label="{{ __('quran_progress.completion') }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="progress flex-grow-1 meter-track w-cap-sm">
                                            <span class="progress-bar {{ $entryPct >= 100 ? 'bg-success' : 'bg-primary' }}"
                                                  style="width: {{ min(100, $entryPct) }}%"></span>
                                        </span>
                                        <span class="tabular fs-sm">{{ number_format($entryPct, 0) }}%</span>
                                    </div>
                                </td>
                                <td data-label="{{ __('quran_progress.updated_by') }}">
                                    {{ $entry->creator?->name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-empty-state icon="bi-clock-history"
                               :title="__('quran_progress.no_history')"
                               :text="__('quran_progress.no_history_text')" />
            @endif
        </x-card>
    </div>
</div>
@endsection
