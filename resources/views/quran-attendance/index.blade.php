@extends('layouts.app')

@section('title', __('quran_attendance.quran_attendance'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('quran_attendance.quran_attendance') }}</h4>
    @can('create', App\Models\QuranAttendance::class)
        <a href="{{ route('quran-attendance.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('quran_attendance.mark_attendance') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">{{ __('quran_attendance.all_classes') }}</option>
                    @foreach($classes as $id => $name)
                        <option value="{{ $id }}" {{ request('class_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">{{ __('quran_attendance.all_teachers') }}</option>
                    @foreach($teachers as $id => $name)
                        <option value="{{ $id }}" {{ request('teacher_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="{{ __('quran_attendance.date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="{{ __('quran_attendance.date_to') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search"></i> {{ __('quran_attendance.filter') }}
                </button>
                <a href="{{ route('quran-attendance.index') }}" class="btn btn-outline-light btn-sm text-dark">
                    <i class="bi bi-x-lg"></i> {{ __('quran_attendance.reset') }}
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('quran_attendance.date') }}</th>
                        <th>{{ __('quran_attendance.class') }}</th>
                        <th>{{ __('quran_attendance.employee') }}</th>
                        <th>{{ __('quran_attendance.status') }}</th>
                        <th>{{ __('quran_attendance.teacher') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendance as $record)
                        <tr>
                            <td>{{ $attendance->firstItem() + $loop->index }}</td>
                            <td>{{ $record->attendance_date->format('d M Y') }}</td>
                            <td>{{ $record->quranClass?->class_name ?? '-' }}</td>
                            <td>{{ $record->employee?->employee_name ?? '-' }}</td>
                            <td>
                                @if($record->attendanceReason)
                                    <span class="badge" style="background-color: {{ $record->attendanceReason->color ?? '#6c757d' }}">
                                        {{ $record->attendanceReason->reason_name }}
                                    </span>
                                @else
                                    <span class="badge bg-success">{{ __('quran_attendance.present') }}</span>
                                @endif
                            </td>
                            <td>{{ $record->teacher?->employee?->employee_name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">{{ __('quran_attendance.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $attendance->withQueryString()->links() }}
    </div>
</div>
@endsection
