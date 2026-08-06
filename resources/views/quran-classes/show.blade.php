@extends('layouts.app')

@section('title', $quranClass->class_name)

@php
    $isFull = $quranClass->active_members_count >= $quranClass->max_strength;
    $fillPct = $quranClass->max_strength > 0
        ? min(100, (int) round($quranClass->active_members_count / $quranClass->max_strength * 100))
        : 0;
@endphp

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.index') }}">{{ __('quran_classes.quran_classes') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $quranClass->class_name }}</li>
@endsection

@section('content')
<x-page-header :title="$quranClass->class_name"
               :subtitle="$quranClass->class_code"
               icon="bi-book">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">{{ __('ui.print') }}</span>
        </button>

        @can('update', $quranClass)
            <a href="{{ route('quran-classes.members.index', $quranClass) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>{{ __('quran_classes.manage_members') }}</span>
            </a>
            <a href="{{ route('quran-classes.edit', $quranClass) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>{{ __('quran_classes.edit') }}</span>
            </a>
        @endcan

        <a href="{{ route('quran-classes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('quran_classes.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="$quranClass->class_name" :subtitle="__('quran_classes.class_code').': '.$quranClass->class_code" />

{{-- ── At a glance ──────────────────────────────────────────────────────── --}}
<div class="auto-grid auto-grid--sm mb-4">
    <x-stat-card :label="__('quran_classes.strength')"
                 :value="$quranClass->active_members_count.' / '.$quranClass->max_strength"
                 icon="bi-people"
                 :tone="$isFull ? 'danger' : 'success'">
        <x-slot:meta>
            <span class="progress w-100" role="img" aria-label="{{ $fillPct }}%">
                <span class="progress-bar {{ $isFull ? 'bg-danger' : 'bg-success' }}" style="width: {{ $fillPct }}%"></span>
            </span>
        </x-slot:meta>
    </x-stat-card>

    <x-stat-card :label="__('quran_classes.teacher')"
                 :value="$quranClass->teacher?->employee?->employee_name ?? '—'"
                 icon="bi-mortarboard"
                 tone="info"
                 :href="$quranClass->teacher ? route('teachers.show', $quranClass->teacher) : null"
                 :hint="$quranClass->teacher ? __('ui.view_details') : null" />

    <x-stat-card :label="__('quran_classes.branch')"
                 :value="$quranClass->branch?->branch_name ?? '—'"
                 icon="bi-building"
                 tone="neutral" />

    <x-stat-card :label="__('quran_classes.schedule')"
                 :value="($quranClass->start_time ? \Carbon\Carbon::parse($quranClass->start_time)->format('h:i A') : '—').' – '.($quranClass->end_time ? \Carbon\Carbon::parse($quranClass->end_time)->format('h:i A') : '—')"
                 icon="bi-clock"
                 tone="warning" />
</div>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <x-card :title="__('quran_classes.class_info')" icon="bi-info-circle" class="mb-3">
            <x-detail-list>
                <x-detail-row :label="__('quran_classes.class_code')">
                    <span class="code-cell">{{ $quranClass->class_code }}</span>
                </x-detail-row>
                <x-detail-row :label="__('quran_classes.class_name')" :value="$quranClass->class_name" />
                <x-detail-row :label="__('quran_classes.status')">
                    <x-status-badge :status="$quranClass->status" />
                </x-detail-row>
                <x-detail-row :label="__('quran_classes.max_strength')" :value="$quranClass->max_strength" />
            </x-detail-list>
        </x-card>

        <x-card :title="__('quran_classes.audit_info')" icon="bi-clock-history">
            <x-detail-list>
                <x-detail-row :label="__('quran_classes.created_by')">
                    {{ $quranClass->creator?->name ?? '—' }}
                    <span class="d-block fs-xs text-subtle">{{ $quranClass->created_at?->format('d M Y, H:i') }}</span>
                </x-detail-row>
                <x-detail-row :label="__('quran_classes.updated_by')">
                    {{ $quranClass->updater?->name ?? '—' }}
                    <span class="d-block fs-xs text-subtle">{{ $quranClass->updated_at?->format('d M Y, H:i') }}</span>
                </x-detail-row>
            </x-detail-list>
        </x-card>
    </div>

    <div class="col-12 col-lg-7">
        <x-card :title="__('quran_classes.active_members')" icon="bi-people" flush class="h-100">
            <x-slot:actions>
                <span class="badge-soft badge-soft-primary">{{ $quranClass->active_members_count }}</span>
                @can('update', $quranClass)
                    <a href="{{ route('quran-classes.members.index', $quranClass) }}" class="btn btn-sm btn-ghost">
                        {{ __('quran_classes.manage_members') }}<i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                @endcan
            </x-slot:actions>

            @if ($quranClass->activeMembers->isNotEmpty())
                <x-table :label="__('quran_classes.active_members')">
                    <thead>
                        <tr>
                            <th scope="col" class="col-num">#</th>
                            <th scope="col">{{ __('employees.employee_name') }}</th>
                            <th scope="col">{{ __('employees.employee_code') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quranClass->activeMembers as $member)
                            <tr>
                                <td class="col-num" data-label="#">{{ $loop->iteration }}</td>
                                <td data-label="{{ __('employees.employee_name') }}">
                                    <div class="cell-primary">
                                        <x-avatar :name="$member->employee_name" />
                                        <span class="cell-primary__text">
                                            <a href="{{ route('employees.show', $member) }}" class="cell-primary__title">{{ $member->employee_name }}</a>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="{{ __('employees.employee_code') }}">
                                    <span class="code-cell">{{ $member->employee_code }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-empty-state icon="bi-person-plus"
                               :title="__('quran_classes.no_members')"
                               :text="__('quran_classes.no_members_text')">
                    @can('update', $quranClass)
                        <a href="{{ route('quran-classes.members.index', $quranClass) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span>{{ __('quran_classes.manage_members') }}</span>
                        </a>
                    @endcan
                </x-empty-state>
            @endif
        </x-card>
    </div>
</div>
@endsection
