@extends('layouts.app')

@section('title', $teacher->employee?->employee_name ?? $teacher->teacher_code)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $teacher->employee?->employee_name ?? '-' }} <small class="text-muted">({{ $teacher->teacher_code }})</small></h4>
    <div class="d-flex gap-2">
        @can('update', $teacher)
            <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil"></i> {{ __('teachers.edit') }}
            </a>
        @endcan
        <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('teachers.back_to_list') }}
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Teacher Information --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('teachers.teacher_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('teachers.teacher_code') }}</td>
                        <td>{{ $teacher->teacher_code }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('teachers.status') }}</td>
                        <td>
                            <span class="badge {{ $teacher->status->badgeClass() }}">
                                {{ $teacher->status->label() }}
                            </span>
                        </td>
                    </tr>
                    @if($teacher->notes)
                    <tr>
                        <td class="text-muted">{{ __('teachers.notes') }}</td>
                        <td>{{ $teacher->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Employee Details --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('teachers.employee_details') }}</h6>
            </div>
            <div class="card-body">
                @if($teacher->employee)
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 40%">{{ __('employees.employee_name') }}</td>
                            <td>
                                <a href="{{ route('employees.show', $teacher->employee) }}">
                                    {{ $teacher->employee->employee_name }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('employees.employee_code') }}</td>
                            <td>{{ $teacher->employee->employee_code }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('employees.mobile') }}</td>
                            <td>{{ $teacher->employee->mobile ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('employees.email') }}</td>
                            <td>{{ $teacher->employee->email ?? '-' }}</td>
                        </tr>
                    </table>
                @else
                    <p class="text-muted mb-0">-</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Assigned Branches --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('teachers.assigned_branches') }}</h6>
            </div>
            <div class="card-body">
                @if($teacher->branches->isNotEmpty())
                    <ul class="list-unstyled mb-0">
                        @foreach($teacher->branches as $branch)
                            <li><i class="bi bi-building me-1 text-muted"></i> {{ $branch->branch_name }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">{{ __('teachers.no_branches') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Audit Information --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('teachers.audit_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('teachers.created_by') }}</td>
                        <td>{{ $teacher->creator?->name ?? '-' }} <small class="text-muted">{{ $teacher->created_at?->format('d M Y H:i') }}</small></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('teachers.updated_by') }}</td>
                        <td>{{ $teacher->updater?->name ?? '-' }} <small class="text-muted">{{ $teacher->updated_at?->format('d M Y H:i') }}</small></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
