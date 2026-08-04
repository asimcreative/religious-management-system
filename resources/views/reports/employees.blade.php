@extends('layouts.app')

@section('title', __('reports.employee_report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('reports.employee_report') }}</h4>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('reports.back_to_reports') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('reports.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_branches') }}</option>
                    @foreach($branches as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['branch_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_departments') }}</option>
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['department_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="designation_id" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_designations') }}</option>
                    @foreach($designations as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['designation_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="employment_status" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_statuses') }}</option>
                    @foreach(App\Enums\Status::cases() as $status)
                        <option value="{{ $status->value }}" {{ isset($filters['employment_status']) && $filters['employment_status'] === (string) $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i> {{ __('reports.filter') }}</button>
                <a href="{{ route('reports.employees') }}" class="btn btn-outline-light btn-sm text-dark"><i class="bi bi-x-lg"></i> {{ __('reports.reset') }}</a>
            </div>
        </form>

        <div class="mb-2 text-muted small">{{ __('reports.total_records') }}: {{ $employees->total() }}</div>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('reports.employee_code') }}</th>
                        <th>{{ __('reports.employee_name') }}</th>
                        <th>{{ __('reports.branch') }}</th>
                        <th>{{ __('reports.department') }}</th>
                        <th>{{ __('reports.designation') }}</th>
                        <th>{{ __('reports.mobile') }}</th>
                        <th>{{ __('reports.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr>
                            <td>{{ $employees->firstItem() + $loop->index }}</td>
                            <td>{{ $emp->employee_code }}</td>
                            <td>{{ $emp->employee_name }}</td>
                            <td>{{ $emp->branch?->branch_name ?? '-' }}</td>
                            <td>{{ $emp->department?->department_name ?? '-' }}</td>
                            <td>{{ $emp->designation?->designation_name ?? '-' }}</td>
                            <td>{{ $emp->mobile ?? '-' }}</td>
                            <td><span class="badge {{ $emp->employment_status->badgeClass() }}">{{ $emp->employment_status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">{{ __('reports.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->withQueryString()->links() }}
    </div>
</div>
@endsection
