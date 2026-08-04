@extends('layouts.app')

@section('title', __('reports.teacher_report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('reports.teacher_report') }}</h4>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('reports.back_to_reports') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
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
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_statuses') }}</option>
                    @foreach(App\Enums\Status::cases() as $status)
                        <option value="{{ $status->value }}" {{ isset($filters['status']) && $filters['status'] === (string) $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i> {{ __('reports.filter') }}</button>
                <a href="{{ route('reports.teachers') }}" class="btn btn-outline-light btn-sm text-dark"><i class="bi bi-x-lg"></i> {{ __('reports.reset') }}</a>
            </div>
        </form>

        <div class="mb-2 text-muted small">{{ __('reports.total_records') }}: {{ $teachers->total() }}</div>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('reports.teacher_code') }}</th>
                        <th>{{ __('reports.teacher_name') }}</th>
                        <th>{{ __('reports.assigned_branches') }}</th>
                        <th>{{ __('reports.total_classes') }}</th>
                        <th>{{ __('reports.active_classes') }}</th>
                        <th>{{ __('reports.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $teacher)
                        <tr>
                            <td>{{ $teachers->firstItem() + $loop->index }}</td>
                            <td>{{ $teacher->teacher_code }}</td>
                            <td>{{ $teacher->getEmployeeName() }}</td>
                            <td>
                                @foreach($teacher->branches as $branch)
                                    <span class="badge bg-light text-dark">{{ $branch->branch_name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $teacher->quran_classes_count }}</td>
                            <td>{{ $teacher->active_classes_count }}</td>
                            <td><span class="badge {{ $teacher->status->badgeClass() }}">{{ $teacher->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('reports.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $teachers->withQueryString()->links() }}
    </div>
</div>
@endsection
