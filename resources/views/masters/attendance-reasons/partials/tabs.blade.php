{{--
    Switches which reason list this page is showing. Each tab is a fully
    independent list underneath (own rows, own CRUD, own import/export) —
    this is purely which one is currently in view.

    Required:
      $type  App\Enums\AttendanceReasonType  Currently active tab.
--}}
<ul class="nav nav-pills mb-3" role="tablist">
    @foreach (App\Enums\AttendanceReasonType::cases() as $case)
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $type === $case ? 'active' : '' }}"
               href="{{ route('masters.attendance-reasons.index', ['type' => $case->value]) }}"
               role="tab"
               @if ($type === $case) aria-current="page" @endif>
                {{ __('masters.attendance_reason_type_'.$case->value) }}
            </a>
        </li>
    @endforeach
</ul>
