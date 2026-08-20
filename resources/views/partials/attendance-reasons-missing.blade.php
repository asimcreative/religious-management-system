{{--
    Shown on an attendance sheet when the company holds no active reason for
    that sheet's module. Presence is stored as attendance_reason_id = NULL, so
    without them every dropdown silently collapses to a single "Present"
    option and the sheet looks like it is working while recording nothing
    anyone can act on.

    Required:
      $manageRoute  string  Route name for that module's reason management screen.
      $moduleLabel  string  e.g. __('masters.salah_attendance_reasons').
--}}
<div class="alert alert-warning no-print" role="alert">
    <i class="bi bi-exclamation-triangle-fill alert__icon" aria-hidden="true"></i>
    <div class="alert__body">
        <strong class="alert__title">{{ __('masters.no_attendance_reasons_title') }}</strong>
        {{ __('masters.no_attendance_reasons_text', ['module' => $moduleLabel]) }}
        @can('viewAny', App\Models\AttendanceReason::class)
            <a href="{{ route($manageRoute) }}" class="alert-link">
                {{ __('masters.no_attendance_reasons_action') }}
            </a>
        @else
            {{ __('masters.no_attendance_reasons_ask_admin') }}
        @endcan
    </div>
</div>
