@extends('layouts.app')

@section('title', __('quran_attendance.mark_attendance'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('quran_attendance.mark_attendance') }}</h4>
    <a href="{{ route('quran-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('quran_attendance.back_to_list') }}
    </a>
</div>

{{-- Step 1: Select Class and Date --}}
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">{{ __('quran_attendance.select_class_date') }}</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">{{ __('quran_attendance.class') }}</label>
                <select name="class_id" class="form-select form-select-sm" required>
                    <option value="">{{ __('quran_attendance.select_class') }}</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                            {{ $class->class_name }} ({{ $class->teacher?->employee?->employee_name ?? '-' }} - {{ $class->branch?->branch_name ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('quran_attendance.date') }}</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" max="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search"></i> {{ __('quran_attendance.load') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Step 2: Mark Attendance --}}
@if($selectedClass && $members->isNotEmpty())
    @if(!$dateAllowed)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ __('quran_attendance.date_not_allowed') }}
        </div>
    @else
        <form method="POST" action="{{ route('quran-attendance.store') }}">
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        {{ $selectedClass->class_name }}
                        <small class="text-muted">- {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y (l)') }}</small>
                    </h6>
                    <span class="badge bg-info">{{ $members->count() }} {{ __('quran_attendance.members') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('quran_attendance.employee') }}</th>
                                    <th>{{ __('quran_attendance.status') }}</th>
                                    <th>{{ __('quran_attendance.remarks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    @php
                                        $existing = $existingAttendance->get($member->id);
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>{{ $member->employee_name }}</strong>
                                            <small class="text-muted d-block">{{ $member->employee_code }}</small>
                                        </td>
                                        <td>
                                            <select name="attendance[{{ $member->id }}]" class="form-select form-select-sm" style="min-width: 150px">
                                                <option value="">{{ __('quran_attendance.present') }}</option>
                                                @foreach($reasons as $reason)
                                                    <option value="{{ $reason->id }}"
                                                        {{ $existing?->attendance_reason_id == $reason->id ? 'selected' : '' }}>
                                                        {{ $reason->reason_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[{{ $member->id }}]" class="form-control form-control-sm"
                                                   value="{{ $existing?->remarks ?? '' }}" placeholder="{{ __('quran_attendance.optional_remarks') }}"
                                                   maxlength="500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ __('quran_attendance.save_attendance') }}
                </button>
                <a href="{{ route('quran-attendance.index') }}" class="btn btn-outline-secondary">{{ __('quran_attendance.cancel') }}</a>
            </div>
        </form>
    @endif
@elseif($selectedClass && $members->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i> {{ __('quran_attendance.no_members_in_class') }}
    </div>
@endif
@endsection
