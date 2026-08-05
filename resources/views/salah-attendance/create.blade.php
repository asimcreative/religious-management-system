@extends('layouts.app')

@section('title', __('salah_attendance.mark_attendance'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('salah_attendance.mark_attendance') }}</h4>
    <a href="{{ route('salah-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('salah_attendance.attendance_history') }}
    </a>
</div>

{{-- Step 1: Select Jamaat & Date --}}
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">{{ __('salah_attendance.step1') }}</h6>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="{{ route('salah-attendance.create') }}" class="row g-3">
            <div class="col-md-4">
                <label for="jamaat_id" class="form-label">{{ __('salah_attendance.jamaat') }} <span class="text-danger">*</span></label>
                <select id="jamaat_id" name="jamaat_id" class="form-select auto-load" required>
                    <option value="">{{ __('salah_attendance.select_jamaat') }}</option>
                    @foreach($jamaats as $j)
                        <option value="{{ $j->id }}" {{ $selectedJamaatId == $j->id ? 'selected' : '' }}>
                            {{ $j->jamaat_name }} ({{ $j->branch?->branch_name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="date" class="form-label">{{ __('salah_attendance.date') }} <span class="text-danger">*</span></label>
                <input type="date" id="date" name="date" class="form-control auto-load"
                       value="{{ $selectedDate }}" max="{{ $maxDate }}" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> {{ __('salah_attendance.load_members') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Step 2: Mark All Prayers --}}
@if($selectedJamaat && $members->count() > 0)
    @if(!$dateAllowed)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> {{ __('salah_attendance.date_not_allowed') }}
        </div>
    @elseif($attendanceReadOnly)
        <div class="alert alert-warning">
            <i class="bi bi-lock"></i> {{ __('salah_attendance.attendance_locked') }}
        </div>
    @else
        @if($existingAttendance->isNotEmpty())
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> {{ __('salah_attendance.attendance_exists') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('salah_attendance.step2') }} — {{ $selectedJamaat->jamaat_name }}</h6>
            </div>
            <div class="card-body p-0">
                <form method="POST" action="{{ route('salah-attendance.store') }}">
                    @csrf
                    <input type="hidden" name="jamaat_id" value="{{ $selectedJamaatId }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>{{ __('salah_attendance.employee_name') }}</th>
                                    @foreach($prayers as $prayer)
                                        <th class="text-center" style="min-width:130px">{{ $prayer->prayer_name }}</th>
                                    @endforeach
                                    <th style="min-width:160px">{{ __('salah_attendance.remarks') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $member->employee_name }}</td>
                                        @foreach($prayers as $prayer)
                                            @php
                                                $rec = $existingAttendance->get($member->id)?->get($prayer->id);
                                            @endphp
                                            <td class="text-center">
                                                <select name="attendance[{{ $member->id }}][{{ $prayer->id }}]"
                                                        class="form-select form-select-sm">
                                                    <option value="">{{ __('salah_attendance.present') }}</option>
                                                    @foreach($reasons as $reason)
                                                        <option value="{{ $reason->id }}"
                                                            {{ $rec && $rec->attendance_reason_id == $reason->id ? 'selected' : '' }}>
                                                            {{ $reason->reason_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endforeach
                                        <td>
                                            @php
                                                $anyRec = $existingAttendance->get($member->id)?->first();
                                            @endphp
                                            <input type="text"
                                                   name="remarks[{{ $member->id }}]"
                                                   class="form-control form-control-sm"
                                                   value="{{ $anyRec?->remarks }}"
                                                   maxlength="500"
                                                   placeholder="{{ __('salah_attendance.remarks') }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> {{ __('salah_attendance.save_attendance') }}
                        </button>
                    </div>
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

@push('scripts')
<script>
    // Auto-submit filter form when both Jamaat and Date are selected
    document.querySelectorAll('.auto-load').forEach(function (el) {
        el.addEventListener('change', function () {
            var jamaatId = document.getElementById('jamaat_id').value;
            var date = document.getElementById('date').value;
            if (jamaatId && date) {
                document.getElementById('filterForm').submit();
            }
        });
    });
</script>
@endpush
