@extends('layouts.app')

@section('title', $teacher->employee?->employee_name ?? $teacher->teacher_code)

@php($teacherName = $teacher->employee?->employee_name ?? $teacher->teacher_code)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('teachers.index') }}">{{ __('teachers.teachers') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $teacherName }}</li>
@endsection

@section('content')
<x-page-header :title="$teacherName" :subtitle="$teacher->teacher_code">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">{{ __('ui.print') }}</span>
        </button>

        @can('update', $teacher)
            <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>{{ __('teachers.edit') }}</span>
            </a>
        @endcan

        <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('teachers.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="$teacherName" :subtitle="__('teachers.teacher_code').': '.$teacher->teacher_code" />

<div class="row g-3">
    {{-- Not h-100: the identity card sizes to its own content instead of being
         stretched to match the taller data column beside it. --}}
    <div class="col-12 col-lg-4">
        <x-card body-class="text-center">
            <x-avatar :name="$teacherName" size="xl" class="mb-3 mx-auto" />
            <h2 class="h5 mb-1">{{ $teacherName }}</h2>
            <p class="code-cell mb-2">{{ $teacher->teacher_code }}</p>
            <x-status-badge :status="$teacher->status" />

            @if ($teacher->employee)
                <div class="mt-3 pt-3 border-top border-subtle text-start fs-md d-flex flex-column gap-2">
                    <div class="row-center">
                        <i class="bi bi-telephone text-soft" aria-hidden="true"></i>
                        @if ($teacher->employee->mobile)
                            <a href="tel:{{ $teacher->employee->mobile }}" class="mono">{{ $teacher->employee->mobile }}</a>
                        @else
                            <span class="dash">{{ __('ui.not_provided') }}</span>
                        @endif
                    </div>
                    <div class="row-center">
                        <i class="bi bi-envelope text-soft" aria-hidden="true"></i>
                        @if ($teacher->employee->email)
                            <a href="mailto:{{ $teacher->employee->email }}" class="truncate">{{ $teacher->employee->email }}</a>
                        @else
                            <span class="dash">{{ __('ui.not_provided') }}</span>
                        @endif
                    </div>

                    @can('view', $teacher->employee)
                        <a href="{{ route('employees.show', $teacher->employee) }}" class="btn btn-outline-secondary btn-sm mt-2 no-print">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <span>{{ __('teachers.view_employee_record') }}</span>
                        </a>
                    @endcan
                </div>
            @endif
        </x-card>
    </div>

    <div class="col-12 col-lg-8">
        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <x-card :title="__('teachers.teacher_info')" icon="bi-mortarboard" class="h-100">
                    <x-detail-list>
                        <x-detail-row :label="__('teachers.teacher_code')">
                            <span class="code-cell">{{ $teacher->teacher_code }}</span>
                        </x-detail-row>
                        <x-detail-row :label="__('teachers.status')">
                            <x-status-badge :status="$teacher->status" />
                        </x-detail-row>
                        <x-detail-row :label="__('teachers.notes')" :value="$teacher->notes" />
                    </x-detail-list>
                </x-card>
            </div>

            <div class="col-12 col-xl-6">
                <x-card :title="__('teachers.employee_details')" icon="bi-person-badge" class="h-100">
                    @if ($teacher->employee)
                        <x-detail-list>
                            <x-detail-row :label="__('employees.employee_name')">
                                @can('view', $teacher->employee)
                                    <a href="{{ route('employees.show', $teacher->employee) }}">{{ $teacher->employee->employee_name }}</a>
                                @else
                                    {{ $teacher->employee->employee_name }}
                                @endcan
                            </x-detail-row>
                            <x-detail-row :label="__('employees.employee_code')">
                                <span class="code-cell">{{ $teacher->employee->employee_code }}</span>
                            </x-detail-row>
                            <x-detail-row :label="__('employees.mobile')" :value="$teacher->employee->mobile" />
                            <x-detail-row :label="__('employees.email')" :value="$teacher->employee->email" />
                        </x-detail-list>
                    @else
                        <x-empty-state size="sm" icon="bi-person-x" :title="__('teachers.no_employee_linked')" />
                    @endif
                </x-card>
            </div>

            <div class="col-12 col-xl-6">
                <x-card :title="__('teachers.assigned_branches')" icon="bi-building" class="h-100">
                    <x-slot:actions>
                        <span class="badge-soft badge-soft-neutral">{{ $teacher->branches->count() }}</span>
                    </x-slot:actions>

                    @if ($teacher->branches->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($teacher->branches as $branch)
                                <span class="chip"><i class="bi bi-building" aria-hidden="true"></i><span>{{ $branch->branch_name }}</span></span>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state size="sm" icon="bi-building" :title="__('teachers.no_branches')" />
                    @endif
                </x-card>
            </div>

            <div class="col-12 col-xl-6">
                <x-card :title="__('teachers.audit_info')" icon="bi-clock-history" class="h-100">
                    <x-detail-list>
                        <x-detail-row :label="__('teachers.created_by')">
                            {{ $teacher->creator?->name ?? '—' }}
                            <span class="d-block fs-xs text-subtle">{{ $teacher->created_at?->format('d M Y, H:i') }}</span>
                        </x-detail-row>
                        <x-detail-row :label="__('teachers.updated_by')">
                            {{ $teacher->updater?->name ?? '—' }}
                            <span class="d-block fs-xs text-subtle">{{ $teacher->updated_at?->format('d M Y, H:i') }}</span>
                        </x-detail-row>
                    </x-detail-list>
                </x-card>
            </div>
        </div>
    </div>
</div>
@endsection
