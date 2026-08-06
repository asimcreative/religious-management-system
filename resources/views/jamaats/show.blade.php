@extends('layouts.app')

@section('title', $jamaat->jamaat_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('jamaats.index') }}">{{ __('jamaats.jamaats') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $jamaat->jamaat_name }}</li>
@endsection

@section('content')
<x-page-header :title="$jamaat->jamaat_name"
               :subtitle="$jamaat->jamaat_number"
               icon="bi-people-fill">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">{{ __('ui.print') }}</span>
        </button>

        @can('create', App\Models\Jamaat::class)
            <a href="{{ route('jamaats.members.index', $jamaat) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>{{ __('jamaats.manage_members') }}</span>
            </a>
        @endcan

        @can('update', $jamaat)
            <a href="{{ route('jamaats.edit', $jamaat) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>{{ __('jamaats.edit') }}</span>
            </a>
        @endcan

        <a href="{{ route('jamaats.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('jamaats.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="$jamaat->jamaat_name" :subtitle="__('jamaats.jamaat_number').': '.$jamaat->jamaat_number" />

<div class="auto-grid auto-grid--sm mb-4">
    <x-stat-card :label="__('jamaats.members_count')"
                 :value="number_format($jamaat->active_members_count)"
                 icon="bi-people"
                 tone="primary" />

    <x-stat-card :label="__('jamaats.leader')"
                 :value="$jamaat->leader?->employee_name ?? '—'"
                 icon="bi-person-badge"
                 tone="info" />

    <x-stat-card :label="__('jamaats.vice_leader')"
                 :value="$jamaat->viceLeader?->employee_name ?? '—'"
                 icon="bi-person"
                 tone="neutral" />

    <x-stat-card :label="__('jamaats.branch')"
                 :value="$jamaat->branch?->branch_name ?? '—'"
                 icon="bi-building"
                 tone="warning" />
</div>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <x-card :title="__('jamaats.jamaat_info')" icon="bi-info-circle" class="mb-3">
            <x-detail-list>
                <x-detail-row :label="__('jamaats.jamaat_number')">
                    <span class="code-cell">{{ $jamaat->jamaat_number }}</span>
                </x-detail-row>
                <x-detail-row :label="__('jamaats.jamaat_name')" :value="$jamaat->jamaat_name" />
                <x-detail-row :label="__('jamaats.branch')" :value="$jamaat->branch?->branch_name" />
                <x-detail-row :label="__('jamaats.status')">
                    <x-status-badge :status="$jamaat->status" />
                </x-detail-row>
            </x-detail-list>
        </x-card>

        <x-card :title="__('jamaats.audit_info')" icon="bi-clock-history">
            <x-detail-list>
                <x-detail-row :label="__('jamaats.created_by')">
                    {{ $jamaat->creator?->name ?? '—' }}
                    <span class="d-block fs-xs text-subtle">{{ $jamaat->created_at?->format('d M Y, H:i') }}</span>
                </x-detail-row>
                <x-detail-row :label="__('jamaats.updated_by')">
                    {{ $jamaat->updater?->name ?? '—' }}
                    <span class="d-block fs-xs text-subtle">{{ $jamaat->updated_at?->format('d M Y, H:i') }}</span>
                </x-detail-row>
            </x-detail-list>
        </x-card>
    </div>

    <div class="col-12 col-lg-7">
        <x-card :title="__('jamaats.active_members')" icon="bi-people" flush class="h-100">
            <x-slot:actions>
                <span class="badge-soft badge-soft-primary">{{ $jamaat->active_members_count }}</span>
                @can('create', App\Models\Jamaat::class)
                    <a href="{{ route('jamaats.members.index', $jamaat) }}" class="btn btn-sm btn-ghost">
                        {{ __('jamaats.manage_members') }}<i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                @endcan
            </x-slot:actions>

            @if ($jamaat->activeMembers->isNotEmpty())
                <x-table :label="__('jamaats.active_members')">
                    <thead>
                        <tr>
                            <th scope="col" class="col-num">#</th>
                            <th scope="col">{{ __('jamaats.employee_name') }}</th>
                            <th scope="col">{{ __('jamaats.employee_code') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jamaat->activeMembers as $member)
                            <tr>
                                <td class="col-num" data-label="#">{{ $loop->iteration }}</td>
                                <td data-label="{{ __('jamaats.employee_name') }}">
                                    <div class="cell-primary">
                                        <x-avatar :name="$member->employee_name" />
                                        <span class="cell-primary__text">
                                            <span class="cell-primary__title">{{ $member->employee_name }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="{{ __('jamaats.employee_code') }}">
                                    <span class="code-cell">{{ $member->employee_code }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @else
                <x-empty-state icon="bi-person-plus"
                               :title="__('jamaats.no_active_members')"
                               :text="__('jamaats.no_active_members_text')">
                    @can('create', App\Models\Jamaat::class)
                        <a href="{{ route('jamaats.members.index', $jamaat) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span>{{ __('jamaats.manage_members') }}</span>
                        </a>
                    @endcan
                </x-empty-state>
            @endif
        </x-card>
    </div>
</div>
@endsection
