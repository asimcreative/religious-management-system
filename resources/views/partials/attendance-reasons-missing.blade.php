{{--
    Shown on both attendance sheets when the company holds no active attendance
    reason. Presence is stored as attendance_reason_id = NULL, so without them
    every dropdown silently collapses to a single "Present" option and the sheet
    looks like it is working while recording nothing anyone can act on.
--}}
<div class="alert alert-warning no-print" role="alert">
    <i class="bi bi-exclamation-triangle-fill alert__icon" aria-hidden="true"></i>
    <div class="alert__body">
        <strong class="alert__title">{{ __('masters.no_attendance_reasons_title') }}</strong>
        {{ __('masters.no_attendance_reasons_text') }}
        @can('viewAny', App\Models\AttendanceReason::class)
            <a href="{{ route('masters.attendance-reasons.index') }}" class="alert-link">
                {{ __('masters.no_attendance_reasons_action') }}
            </a>
        @else
            {{ __('masters.no_attendance_reasons_ask_admin') }}
        @endcan
    </div>
</div>
