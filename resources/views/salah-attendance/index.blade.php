@extends('layouts.app')

@section('title', __('salah_attendance.attendance_history'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('salah_attendance.attendance_history') }}</h4>
    @can('create', App\Models\SalahAttendance::class)
        <a href="{{ route('salah-attendance.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('salah_attendance.mark_attendance') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('salah_attendance.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="jamaat_id" class="form-select form-select-sm">
                    <option value="">{{ __('salah_attendance.all_jamaats') }}</option>
                    @foreach($jamaats as $id => $name)
                        <option value="{{ $id }}" {{ request('jamaat_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="prayer_id" class="form-select form-select-sm">
                    <option value="">{{ __('salah_attendance.all_prayers') }}</option>
                    @foreach($prayers as $id => $name)
                        <option value="{{ $id }}" {{ request('prayer_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       placeholder="{{ __('salah_attendance.date_from') }}" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       placeholder="{{ __('salah_attendance.date_to') }}" value="{{ request('date_to') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search"></i> {{ __('salah_attendance.filter') }}
                </button>
                <a href="{{ route('salah-attendance.index') }}" class="btn btn-outline-light btn-sm text-dark">
                    <i class="bi bi-x-lg"></i> {{ __('salah_attendance.reset') }}
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('salah_attendance.date') }}</th>
                        <th>{{ __('salah_attendance.prayer') }}</th>
                        <th>{{ __('salah_attendance.jamaat') }}</th>
                        <th>{{ __('salah_attendance.employee_name') }}</th>
                        <th>{{ __('salah_attendance.attendance_status') }}</th>
                        <th>{{ __('salah_attendance.remarks') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendance as $record)
                        <tr>
                            <td>{{ $attendance->firstItem() + $loop->index }}</td>
                            <td>{{ $record->attendance_date->format('d M Y') }}</td>
                            <td>{{ $record->prayer?->prayer_name ?? '-' }}</td>
                            <td>{{ $record->jamaat?->jamaat_name ?? '-' }}</td>
                            <td>{{ $record->employee?->employee_name ?? '-' }}</td>
                            <td>
                                @if($record->isPresent())
                                    <span class="badge bg-success">{{ __('salah_attendance.present') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ $record->attendanceReason?->reason_name ?? '-' }}</span>
                                @endif
                            </td>
                            <td>{{ $record->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">{{ __('salah_attendance.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $attendance->withQueryString()->links() }}
    </div>
</div>
@endsection
