@extends('layouts.app')

@section('title', $employee->employee_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">{{ __('employees.employees') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $employee->employee_name }}</li>
@endsection

@section('content')
<x-page-header :title="$employee->employee_name"
               :subtitle="$employee->employee_code">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">{{ __('ui.print') }}</span>
        </button>

        @can('update', $employee)
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>{{ __('employees.edit') }}</span>
            </a>
        @endcan

        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span class="d-none d-md-inline">{{ __('employees.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-print-header :title="$employee->employee_name" :subtitle="__('employees.employee_code').': '.$employee->employee_code" />

<div class="row g-3">

    {{-- ── Identity summary ─────────────────────────────────────────────── --}}
    {{-- Not h-100: the identity card sizes to its own content instead of being
         stretched to match the taller data column beside it. --}}
    <div class="col-12 col-lg-4">
        <x-card body-class="text-center">
            @if ($employee->photo)
                <img src="{{ route('employees.photo', $employee) }}"
                     alt="{{ $employee->employee_name }}"
                     class="radius-lg mb-3"
                     style="width:110px;height:110px;object-fit:cover;"
                     loading="lazy" decoding="async">
            @else
                <x-avatar :name="$employee->employee_name" size="xl" class="mb-3 mx-auto" />
            @endif

            <h2 class="h5 mb-1">{{ $employee->employee_name }}</h2>
            <p class="code-cell mb-2">{{ $employee->employee_code }}</p>
            <x-status-badge :status="$employee->employment_status" class="mb-3" />

            <div class="d-flex flex-column gap-2 mt-3 pt-3 border-top border-subtle text-start fs-md">
                <div class="row-center">
                    <i class="bi bi-telephone text-soft" aria-hidden="true"></i>
                    @if ($employee->mobile)
                        <a href="tel:{{ $employee->mobile }}" class="mono">{{ $employee->mobile }}</a>
                    @else
                        <span class="dash">{{ __('ui.not_provided') }}</span>
                    @endif
                </div>
                <div class="row-center">
                    <i class="bi bi-envelope text-soft" aria-hidden="true"></i>
                    @if ($employee->email)
                        <a href="mailto:{{ $employee->email }}" class="truncate">{{ $employee->email }}</a>
                    @else
                        <span class="dash">{{ __('ui.not_provided') }}</span>
                    @endif
                </div>
                <div class="row-center">
                    <i class="bi bi-building text-soft" aria-hidden="true"></i>
                    <span class="truncate">{{ $employee->branch?->branch_name ?? __('ui.not_provided') }}</span>
                </div>
            </div>
        </x-card>
    </div>

    {{-- ── Details ──────────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <x-card :title="__('employees.personal_info')" icon="bi-person-badge" class="h-100">
                    <x-detail-list>
                        <x-detail-row :label="__('employees.employee_code')">
                            <span class="code-cell">{{ $employee->employee_code }}</span>
                        </x-detail-row>
                        <x-detail-row :label="__('employees.employee_name')" :value="$employee->employee_name" />
                        <x-detail-row :label="__('employees.cnic')">
                            {{-- Masked: full CNIC is personal data and is never rendered in a page view. --}}
                            @if ($employee->cnic)
                                <span class="mono">{{ substr($employee->cnic, 0, 5) }}-XXXXXXX-X</span>
                            @endif
                        </x-detail-row>
                        <x-detail-row :label="__('employees.mobile')" :value="$employee->mobile" />
                        <x-detail-row :label="__('employees.email')" :value="$employee->email" />
                        <x-detail-row :label="__('employees.dob')" :value="$employee->dob?->format('d M Y')" />
                        <x-detail-row :label="__('employees.gender')" :value="$employee->gender ? __('employees.'.$employee->gender) : null" />
                    </x-detail-list>
                </x-card>
            </div>

            <div class="col-12 col-xl-6">
                <x-card :title="__('employees.organization_info')" icon="bi-diagram-3" class="h-100">
                    <x-detail-list>
                        <x-detail-row :label="__('employees.branch')" :value="$employee->branch?->branch_name" />
                        <x-detail-row :label="__('employees.department')" :value="$employee->department?->department_name" />
                        <x-detail-row :label="__('employees.designation')" :value="$employee->designation?->designation_name" />
                        <x-detail-row :label="__('employees.status')">
                            <x-status-badge :status="$employee->employment_status" />
                        </x-detail-row>
                    </x-detail-list>
                </x-card>
            </div>

            <div class="col-12 col-xl-6">
                <x-card :title="__('employees.religious_info')" icon="bi-book" class="h-100">
                    <x-detail-list>
                        <x-detail-row :label="__('employees.quran_department')" :value="$employee->quranDepartment?->department_name" />
                        <x-detail-row :label="__('employees.quran_status')" :value="$employee->quranStatusRelation?->status_name" />
                    </x-detail-list>
                </x-card>
            </div>

            <div class="col-12 col-xl-6">
                <x-card :title="__('employees.audit_info')" icon="bi-clock-history" class="h-100">
                    <x-detail-list>
                        <x-detail-row :label="__('employees.created_by')">
                            {{ $employee->creator?->name ?? '—' }}
                            <span class="d-block fs-xs text-subtle">{{ $employee->created_at?->format('d M Y, H:i') }}</span>
                        </x-detail-row>
                        <x-detail-row :label="__('employees.updated_by')">
                            {{ $employee->updater?->name ?? '—' }}
                            <span class="d-block fs-xs text-subtle">{{ $employee->updated_at?->format('d M Y, H:i') }}</span>
                        </x-detail-row>
                    </x-detail-list>
                </x-card>
            </div>

            @if ($employee->notes)
                <div class="col-12">
                    <x-card :title="__('employees.notes')" icon="bi-sticky">
                        <p class="mb-0 fs-md" style="white-space: pre-line;">{{ $employee->notes }}</p>
                    </x-card>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
