@extends('layouts.app')

@section('title', __('salah_attendance.mark_attendance'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('salah_attendance.mark_attendance') }}</h4>
    <a href="{{ route('salah-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('salah_attendance.attendance_history') }}
    </a>
</div>

{{-- Step 1: Select Jamaat, Prayer & Date --}}
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">{{ __('salah_attendance.step1') }}</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('salah-attendance.create') }}" class="row g-3">
            <div class="col-md-3">
                <label for="jamaat_id" class="form-label">{{ __('salah_attendance.jamaat') }} <span class="text-danger">*</span></label>
                <select id="jamaat_id" name="jamaat_id" class="form-select" required>
                    <option value="">{{ __('salah_attendance.select_jamaat') }}</option>
                    @foreach($jamaats as $j)
                        <option value="{{ $j->id }}" {{ $selectedJamaatId == $j->id ? 'selected' : '' }}>
                            {{ $j->jamaat_name }} ({{ $j->branch?->branch_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="prayer_id" class="form-label">{{ __('salah_attendance.prayer') }} <span class="text-danger">*</span></label>
                <select id="prayer_id" name="prayer_id" class="form-select" required>
                    <option value="">{{ __('salah_attendance.select_prayer') }}</option>
                    @foreach($prayers as $prayer)
                        <option value="{{ $prayer->id }}" {{ $selectedPrayerId == $prayer->id ? 'selected' : '' }}>
                            {{ $prayer->prayer_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">{{ __('salah_attendance.date') }} <span class="text-danger">*</span></label>
                <input type="date" id="date" name="date" class="form-control"
                       value="{{ $selectedDate }}" max="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> {{ __('salah_attendance.load_members') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Step 2: Mark Attendance --}}
@if($selectedJamaat && $members->count() > 0)
    @if(!$dateAllowed)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> {{ __('salah_attendance.date_not_allowed') }}
        </div>
    @else
        @if($existingAttendance->count() > 0)
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> {{ __('salah_attendance.attendance_exists') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('salah_attendance.step2') }} — {{ $selectedJamaat->jamaat_name }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('salah-attendance.store') }}">
                    @csrf
                    <input type="hidden" name="jamaat_id" value="{{ $selectedJamaatId }}">
                    <input type="hidden" name="prayer_id" value="{{ $selectedPrayerId }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('salah_attendance.employee_name') }}</th>
                                    <th>{{ __('salah_attendance.reason') }}</th>
                                    <th>{{ __('salah_attendance.remarks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    @php
                                        $existing = $existingAttendance->get($member->id);
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $member->employee_name }}</td>
                                        <td>
                                            <select name="attendance[{{ $member->id }}]" class="form-select form-select-sm">
                                                <option value="">{{ __('salah_attendance.select_reason') }}</option>
                                                @foreach($reasons as $reason)
                                                    <option value="{{ $reason->id }}"
                                                        {{ $existing && $existing->attendance_reason_id == $reason->id ? 'selected' : '' }}>
                                                        {{ $reason->reason_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[{{ $member->id }}]" class="form-control form-control-sm"
                                                   value="{{ $existing?->remarks }}" maxlength="500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> {{ __('salah_attendance.save_attendance') }}
                    </button>
                </form>
            </div>
        </div>
    @endif
@elseif($selectedJamaat && $members->count() === 0)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> {{ __('salah_attendance.no_members') }}
    </div>
@endif
@endsection
