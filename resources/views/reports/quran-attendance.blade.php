@extends('layouts.app')

@section('title', __('reports.quran_attendance_report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('reports.quran_attendance_report') }}</h4>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('reports.back_to_reports') }}
    </a>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">{{ __('reports.total') }}</div>
                <h4 class="mb-0">{{ $summary['total'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body py-2">
                <div class="text-muted small">{{ __('reports.present') }}</div>
                <h4 class="mb-0 text-success">{{ $summary['present'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-danger">
            <div class="card-body py-2">
                <div class="text-muted small">{{ __('reports.absent') }}</div>
                <h4 class="mb-0 text-danger">{{ $summary['absent'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-primary">
            <div class="card-body py-2">
                <div class="text-muted small">{{ __('reports.attendance_rate') }}</div>
                <h4 class="mb-0 text-primary">{{ $summary['percentage'] }}%</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('reports.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">{{ __('reports.all_classes') }}</option>
                    @foreach($classes as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['class_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
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
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i> {{ __('reports.filter') }}</button>
                <a href="{{ route('reports.quran-attendance') }}" class="btn btn-outline-light btn-sm text-dark"><i class="bi bi-x-lg"></i> {{ __('reports.reset') }}</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('reports.date') }}</th>
                        <th>{{ __('reports.class') }}</th>
                        <th>{{ __('reports.teacher') }}</th>
                        <th>{{ __('reports.employee_name') }}</th>
                        <th>{{ __('reports.attendance_status') }}</th>
                        <th>{{ __('reports.remarks') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendance as $record)
                        <tr>
                            <td>{{ $attendance->firstItem() + $loop->index }}</td>
                            <td>{{ $record->attendance_date->format('d M Y') }}</td>
                            <td>{{ $record->quranClass?->class_name ?? '-' }}</td>
                            <td>{{ $record->teacher?->getEmployeeName() ?? '-' }}</td>
                            <td>{{ $record->employee?->employee_name ?? '-' }}</td>
                            <td>
                                @if($record->attendance_reason_id === null)
                                    <span class="badge bg-success">{{ __('reports.present') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ $record->attendanceReason?->reason_name ?? '-' }}</span>
                                @endif
                            </td>
                            <td>{{ $record->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('reports.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $attendance->withQueryString()->links() }}
    </div>
</div>
@endsection
