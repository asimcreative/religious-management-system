@extends('layouts.app')

@section('title', __('employees.employees'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('employees.employees') }}</h4>
    @can('create', App\Models\Employee::class)
        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('employees.add_new') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        {{-- Search & Filters --}}
        <form method="GET" class="mb-3">
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="{{ __('employees.search_placeholder') }}" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">{{ __('employees.all_branches') }}</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">{{ __('employees.all_departments') }}</option>
                        @foreach($departments as $id => $name)
                            <option value="{{ $id }}" {{ request('department_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="designation_id" class="form-select form-select-sm">
                        <option value="">{{ __('employees.all_designations') }}</option>
                        @foreach($designations as $id => $name)
                            <option value="{{ $id }}" {{ request('designation_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="employment_status" class="form-select form-select-sm">
                        <option value="">{{ __('employees.all_statuses') }}</option>
                        @foreach(App\Enums\Status::cases() as $status)
                            <option value="{{ $status->value }}" {{ request('employment_status') === (string) $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-2">
                    <select name="quran_department_id" class="form-select form-select-sm">
                        <option value="">{{ __('employees.all_quran_depts') }}</option>
                        @foreach($quranDepartments as $id => $name)
                            <option value="{{ $id }}" {{ request('quran_department_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="quran_status_id" class="form-select form-select-sm">
                        <option value="">{{ __('employees.all_quran_statuses') }}</option>
                        @foreach($quranStatuses as $id => $name)
                            <option value="{{ $id }}" {{ request('quran_status_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-search"></i> {{ __('employees.filter') }}
                    </button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-light btn-sm text-dark">
                        <i class="bi bi-x-lg"></i> {{ __('employees.reset') }}
                    </a>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('employees.employee_code') }}</th>
                        <th>{{ __('employees.employee_name') }}</th>
                        <th>{{ __('employees.department') }}</th>
                        <th>{{ __('employees.designation') }}</th>
                        <th>{{ __('employees.branch') }}</th>
                        <th>{{ __('employees.mobile') }}</th>
                        <th>{{ __('employees.status') }}</th>
                        <th>{{ __('employees.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>{{ $employees->firstItem() + $loop->index }}</td>
                            <td>{{ $employee->employee_code }}</td>
                            <td>
                                <a href="{{ route('employees.show', $employee) }}">
                                    {{ $employee->employee_name }}
                                </a>
                            </td>
                            <td>{{ $employee->department?->department_name ?? '-' }}</td>
                            <td>{{ $employee->designation?->designation_name ?? '-' }}</td>
                            <td>{{ $employee->branch?->branch_name ?? '-' }}</td>
                            <td>{{ $employee->mobile ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $employee->employment_status->badgeClass() }}">
                                    {{ $employee->employment_status->label() }}
                                </span>
                            </td>
                            <td>
                                @can('view', $employee)
                                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-info btn-sm" title="{{ __('employees.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                                @can('update', $employee)
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-primary btn-sm" title="{{ __('employees.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $employee)
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('employees.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('employees.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">{{ __('employees.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $employees->withQueryString()->links() }}
    </div>
</div>
@endsection
