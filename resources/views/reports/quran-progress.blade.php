@extends('layouts.app')

@section('title', __('reports.quran_progress_report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('reports.quran_progress_report') }}</h4>
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
                <select name="quran_department_id" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_departments_quran') }}</option>
                    @foreach($quranDepartments as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['quran_department_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="quran_status_id" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_statuses_quran') }}</option>
                    @foreach($quranStatuses as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['quran_status_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_teachers') }}</option>
                    @foreach($teachers as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['teacher_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i> {{ __('reports.filter') }}</button>
                <a href="{{ route('reports.quran-progress') }}" class="btn btn-outline-light btn-sm text-dark"><i class="bi bi-x-lg"></i> {{ __('reports.reset') }}</a>
            </div>
        </form>

        <div class="mb-2 text-muted small">{{ __('reports.total_records') }}: {{ $progress->total() }}</div>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('reports.employee') }}</th>
                        <th>{{ __('reports.teacher') }}</th>
                        <th>{{ __('reports.quran_department') }}</th>
                        <th>{{ __('reports.quran_status') }}</th>
                        <th>{{ __('reports.current_lesson') }}</th>
                        <th>{{ __('reports.completion') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($progress as $p)
                        <tr>
                            <td>{{ $progress->firstItem() + $loop->index }}</td>
                            <td>{{ $p->employee?->employee_name ?? '-' }}</td>
                            <td>{{ $p->teacher?->getEmployeeName() ?? '-' }}</td>
                            <td>{{ $p->quranDepartment?->department_name ?? '-' }}</td>
                            <td>{{ $p->quranStatus?->status_name ?? '-' }}</td>
                            <td>{{ $p->current_lesson ?? '-' }}</td>
                            <td>
                                <div class="progress" style="height: 20px; min-width: 80px;">
                                    <div class="progress-bar {{ $p->completion_percentage >= 80 ? 'bg-success' : ($p->completion_percentage >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                         style="width: {{ $p->completion_percentage }}%">
                                        {{ $p->completion_percentage }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('reports.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $progress->withQueryString()->links() }}
    </div>
</div>
@endsection
