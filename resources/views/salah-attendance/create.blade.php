@extends('layouts.app')

@section('title', __('salah_attendance.mark_attendance'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('salah-attendance.index') }}">{{ __('salah_attendance.salah_attendance') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('salah_attendance.mark_attendance') }}</li>
@endsection

@section('content')
<x-page-header :title="__('salah_attendance.mark_attendance')"
               :subtitle="__('salah_attendance.mark_subtitle')"
               icon="bi-calendar-plus">
    <x-slot:actions>
        <a href="{{ route('salah-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('salah_attendance.attendance_history') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

{{-- ── Step 1 — choose jamaat and date ─────────────────────────────────── --}}
<x-card class="mb-3">
    <x-slot:title>
        <span class="badge-soft badge-soft-primary badge-soft--plain me-1">1</span>
        {{ __('salah_attendance.step1') }}
    </x-slot:title>

    <form id="filterForm" method="GET" action="{{ route('salah-attendance.create') }}" class="row g-3 align-items-end">
        <div class="col-12 col-lg-5">
            <label for="jamaat_id" class="form-label">
                {{ __('salah_attendance.jamaat') }}<span class="req" aria-hidden="true">*</span>
            </label>
            <select id="jamaat_id" name="jamaat_id" class="form-select auto-load" required>
                <option value="">{{ __('salah_attendance.select_jamaat') }}</option>
                @foreach ($jamaats as $j)
                    <option value="{{ $j->id }}" @selected($selectedJamaatId == $j->id)>
                        {{ $j->jamaat_name }}@if ($j->branch?->branch_name) — {{ $j->branch->branch_name }}@endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-8 col-lg-4">
            <label for="date" class="form-label">
                {{ __('salah_attendance.date') }}<span class="req" aria-hidden="true">*</span>
            </label>
            <input type="date" id="date" name="date" class="form-control auto-load"
                   value="{{ $selectedDate }}" max="{{ $maxDate }}" required>
        </div>

        <div class="col-4 col-lg-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                <span>{{ __('salah_attendance.load_members') }}</span>
            </button>
        </div>
    </form>
</x-card>

{{-- ── Step 2 — mark every prayer ──────────────────────────────────────── --}}
@if ($selectedJamaat && $members->count() > 0)

    @if (! $dateAllowed)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill alert__icon" aria-hidden="true"></i>
            <div class="alert__body">
                <strong class="alert__title">{{ __('salah_attendance.date_not_allowed_title') }}</strong>
                {{ __('salah_attendance.date_not_allowed') }}
            </div>
        </div>
    @elseif ($attendanceReadOnly)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-lock-fill alert__icon" aria-hidden="true"></i>
            <div class="alert__body">
                <strong class="alert__title">{{ __('salah_attendance.attendance_locked_title') }}</strong>
                {{ __('salah_attendance.attendance_locked') }}
            </div>
        </div>
    @else
        @if ($existingAttendance->isNotEmpty())
            <div class="alert alert-info" role="status">
                <i class="bi bi-info-circle-fill alert__icon" aria-hidden="true"></i>
                <div class="alert__body">{{ __('salah_attendance.attendance_exists') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('salah-attendance.store') }}" data-guard>
            @csrf
            <input type="hidden" name="jamaat_id" value="{{ $selectedJamaatId }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">

            <x-card flush>
                <x-slot:title>
                    <span class="badge-soft badge-soft-primary badge-soft--plain me-1">2</span>
                    {{ $selectedJamaat->jamaat_name }}
                </x-slot:title>
                <x-slot:subtitle>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d M Y') }}</x-slot:subtitle>
                <x-slot:actions>
                    <span class="badge-soft badge-soft-neutral">
                        {{ trans_choice('salah_attendance.member_count', $members->count(), ['count' => $members->count()]) }}
                    </span>
                </x-slot:actions>

                {{-- Bulk setter: marking a whole prayer at once is the common case --}}
                <div class="attendance-summary no-print">
                    <span class="attendance-summary__item">
                        <i class="bi bi-lightning-charge text-soft" aria-hidden="true"></i>
                        {{ __('salah_attendance.bulk_intro') }}
                    </span>

                    <span class="attendance-summary__actions">
                        <label for="bulkPrayer" class="visually-hidden">{{ __('salah_attendance.bulk_prayer_label') }}</label>
                        <select id="bulkPrayer" class="form-select form-select-sm w-auto-field">
                            <option value="[data-prayer]">{{ __('salah_attendance.all_prayers') }}</option>
                            @foreach ($prayers as $prayer)
                                <option value="[data-prayer='{{ $prayer->id }}']">{{ $prayer->prayer_name }}</option>
                            @endforeach
                        </select>

                        <label for="bulkReason" class="visually-hidden">{{ __('salah_attendance.bulk_status_label') }}</label>
                        <select id="bulkReason" class="form-select form-select-sm w-auto-field">
                            <option value="">{{ __('salah_attendance.present') }}</option>
                            @foreach ($reasons as $reason)
                                <option value="{{ $reason->id }}">{{ $reason->reason_name }}</option>
                            @endforeach
                        </select>

                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bulk-apply-dynamic="#bulkPrayer"
                                data-bulk-from="#bulkReason">
                            <i class="bi bi-check2-all" aria-hidden="true"></i>
                            <span>{{ __('salah_attendance.apply') }}</span>
                        </button>
                    </span>
                </div>

                <x-table sticky :stack="false" class="table-pinned" :label="__('salah_attendance.mark_attendance')">
                    <thead>
                        <tr>
                            <th scope="col" class="col-num">#</th>
                            <th scope="col">{{ __('salah_attendance.employee_name') }}</th>
                            @foreach ($prayers as $prayer)
                                <th scope="col" class="text-center" style="min-width: 8.5rem;">{{ $prayer->prayer_name }}</th>
                            @endforeach
                            <th scope="col" style="min-width: 11rem;">{{ __('salah_attendance.remarks') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td class="col-num">{{ $loop->iteration }}</td>

                                <td>
                                    <div class="cell-primary">
                                        <x-avatar :name="$member->employee_name" />
                                        <span class="cell-primary__text">
                                            <span class="cell-primary__title">{{ $member->employee_name }}</span>
                                            <span class="cell-primary__sub code-cell">{{ $member->employee_code }}</span>
                                        </span>
                                    </div>
                                </td>

                                @foreach ($prayers as $prayer)
                                    @php($rec = $existingAttendance->get($member->id)?->get($prayer->id))
                                    <td>
                                        <label class="visually-hidden" for="att_{{ $member->id }}_{{ $prayer->id }}">
                                            {{ $prayer->prayer_name }} — {{ $member->employee_name }}
                                        </label>
                                        <select name="attendance[{{ $member->id }}][{{ $prayer->id }}]"
                                                id="att_{{ $member->id }}_{{ $prayer->id }}"
                                                class="form-select form-select-sm"
                                                data-prayer="{{ $prayer->id }}">
                                            <option value="">{{ __('salah_attendance.present') }}</option>
                                            @foreach ($reasons as $reason)
                                                <option value="{{ $reason->id }}" @selected($rec && $rec->attendance_reason_id == $reason->id)>
                                                    {{ $reason->reason_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                @endforeach

                                <td>
                                    @php($anyRec = $existingAttendance->get($member->id)?->first())
                                    <label class="visually-hidden" for="remarks_{{ $member->id }}">
                                        {{ __('salah_attendance.remarks') }} — {{ $member->employee_name }}
                                    </label>
                                    <input type="text"
                                           name="remarks[{{ $member->id }}]"
                                           id="remarks_{{ $member->id }}"
                                           class="form-control form-control-sm"
                                           value="{{ $anyRec?->remarks }}"
                                           maxlength="500"
                                           placeholder="{{ __('salah_attendance.optional_remarks') }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </x-card>

            <x-form.actions :submit="__('salah_attendance.save_attendance')"
                            :cancel-url="route('salah-attendance.index')"
                            :hint="__('salah_attendance.save_hint')" />
        </form>
    @endif

@elseif ($selectedJamaat && $members->count() === 0)
    <x-card>
        <x-empty-state icon="bi-person-x"
                       :title="__('salah_attendance.no_members_title')"
                       :text="__('salah_attendance.no_members')">
            @can('update', $selectedJamaat)
                <a href="{{ route('jamaats.members.index', $selectedJamaat) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span>{{ __('jamaats.manage_members') }}</span>
                </a>
            @endcan
        </x-empty-state>
    </x-card>
@else
    <x-card>
        <x-empty-state icon="bi-moon-stars"
                       :title="__('salah_attendance.choose_jamaat_title')"
                       :text="__('salah_attendance.choose_jamaat_text')" />
    </x-card>
@endif
@endsection

@push('scripts')
<script>
    // Selecting both a jamaat and a date is always followed by "load" —
    // submitting automatically removes a redundant click.
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
