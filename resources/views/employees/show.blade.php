@extends('layouts.app')

@section('title', $employee->employee_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $employee->employee_name }} <small class="text-muted">({{ $employee->employee_code }})</small></h4>
    <div class="d-flex gap-2">
        @can('update', $employee)
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil"></i> {{ __('employees.edit') }}
            </a>
        @endcan
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('employees.back_to_list') }}
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Personal Information --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('employees.personal_info') }}</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    @if($employee->photo)
                        <div class="col-12 mb-3">
                            <img src="{{ route('employees.photo', $employee) }}" alt="{{ $employee->employee_name }}"
                                 class="rounded" style="max-width: 120px; max-height: 120px; object-fit: cover;">
                        </div>
                    @endif
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('employees.employee_code') }}</td>
                        <td>{{ $employee->employee_code }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.employee_name') }}</td>
                        <td>{{ $employee->employee_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.cnic') }}</td>
                        <td>{{ $employee->cnic ? substr($employee->cnic, 0, 5) . '-XXXXXXX-X' : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.mobile') }}</td>
                        <td>{{ $employee->mobile ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.email') }}</td>
                        <td>{{ $employee->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.dob') }}</td>
                        <td>{{ $employee->dob?->format('d M Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.gender') }}</td>
                        <td>{{ $employee->gender ? ucfirst($employee->gender) : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Organization --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('employees.organization_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('employees.branch') }}</td>
                        <td>{{ $employee->branch?->branch_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.department') }}</td>
                        <td>{{ $employee->department?->department_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.designation') }}</td>
                        <td>{{ $employee->designation?->designation_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.status') }}</td>
                        <td>
                            <span class="badge {{ $employee->employment_status->badgeClass() }}">
                                {{ $employee->employment_status->label() }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Religious Information --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('employees.religious_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('employees.quran_department') }}</td>
                        <td>{{ $employee->quranDepartment?->department_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.quran_status') }}</td>
                        <td>{{ $employee->quranStatusRelation?->status_name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($employee->notes)
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('employees.notes') }}</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $employee->notes }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Audit Information --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('employees.audit_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('employees.created_by') }}</td>
                        <td>{{ $employee->creator?->name ?? '-' }} <small class="text-muted">{{ $employee->created_at?->format('d M Y H:i') }}</small></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('employees.updated_by') }}</td>
                        <td>{{ $employee->updater?->name ?? '-' }} <small class="text-muted">{{ $employee->updated_at?->format('d M Y H:i') }}</small></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
