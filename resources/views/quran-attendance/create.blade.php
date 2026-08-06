@extends('layouts.app')

@section('title', __('quran_attendance.mark_attendance'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-attendance.index') }}">{{ __('quran_attendance.quran_attendance') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_attendance.mark_attendance') }}</li>
@endsection

@section('content')
<x-page-header :title="__('quran_attendance.mark_attendance')"
               :subtitle="__('quran_attendance.mark_subtitle')"
               icon="bi-clipboard-plus">
    <x-slot:actions>
        <a href="{{ route('quran-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('quran_attendance.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

{{-- ── Step 1 — choose class and date ──────────────────────────────────── --}}
<x-card class="mb-3">
    <x-slot:title>
        <span class="badge-soft badge-soft-primary badge-soft--plain me-1">1</span>
        {{ __('quran_attendance.select_class_date') }}
    </x-slot:title>

    <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-lg-6">
            <label for="class_id" class="form-label">
                {{ __('quran_attendance.class') }}<span class="req" aria-hidden="true">*</span>
            </label>
            <select name="class_id" id="class_id" class="form-select" required>
                <option value="">{{ __('quran_attendance.select_class') }}</option>
                @foreach ($classes as $class)
                    @php
                        $context = array_filter([
                            $class->teacher?->employee?->employee_name,
                            $class->branch?->branch_name,
                        ]);
                    @endphp
                    <option value="{{ $class->id }}" @selected($selectedClassId == $class->id)>
                        {{ $class->class_name }}@if ($context) — {{ implode(' · ', $context) }}@endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-8 col-lg-3">
            <label for="date" class="form-label">
                {{ __('quran_attendance.date') }}<span class="req" aria-hidden="true">*</span>
            </label>
            <input type="date" name="date" id="date" class="form-control"
                   value="{{ $selectedDate }}" max="{{ $maxDate }}" required>
        </div>

        <div class="col-4 col-lg-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                <span>{{ __('quran_attendance.load') }}</span>
            </button>
        </div>
    </form>
</x-card>

{{-- ── Step 2 — mark the sheet ─────────────────────────────────────────── --}}
@if ($selectedClass && $members->isNotEmpty())

    @if (! $dateAllowed)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill alert__icon" aria-hidden="true"></i>
            <div class="alert__body">
                <strong class="alert__title">{{ __('quran_attendance.date_not_allowed_title') }}</strong>
                {{ __('quran_attendance.date_not_allowed') }}
            </div>
        </div>
    @elseif ($attendanceReadOnly)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-lock-fill alert__icon" aria-hidden="true"></i>
            <div class="alert__body">
                <strong class="alert__title">{{ __('quran_attendance.attendance_locked_title') }}</strong>
                {{ __('quran_attendance.attendance_locked') }}
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('quran-attendance.store') }}" data-guard>
            @csrf
            <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">

            <x-card flush>
                <x-slot:title>
                    <span class="badge-soft badge-soft-primary badge-soft--plain me-1">2</span>
                    {{ $selectedClass->class_name }}
                </x-slot:title>
                <x-slot:subtitle>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d M Y') }}</x-slot:subtitle>
                <x-slot:actions>
                    <span class="badge-soft badge-soft-neutral">
                        {{ trans_choice('quran_attendance.member_count', $members->count(), ['count' => $members->count()]) }}
                    </span>
                </x-slot:actions>

                {{-- Live tally + bulk setter --}}
                <div class="attendance-summary no-print">
                    <span class="attendance-summary__item">
                        <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                        {{ __('quran_attendance.present') }}:
                        <span class="attendance-summary__value" id="tallyPresent" aria-live="polite">0</span>
                    </span>

                    <span class="attendance-summary__item">
                        <i class="bi bi-x-circle-fill text-danger" aria-hidden="true"></i>
                        {{ __('quran_attendance.absent') }}:
                        <span class="attendance-summary__value" id="tallyAbsent" aria-live="polite">0</span>
                    </span>

                    <span class="progress attendance-summary__bar" aria-hidden="true">
                        <span class="progress-bar bg-success" id="tallyBar" style="width: 100%"></span>
                    </span>

                    <span class="attendance-summary__actions">
                        <label for="bulkReason" class="visually-hidden">{{ __('quran_attendance.bulk_set_label') }}</label>
                        <select id="bulkReason" class="form-select form-select-sm w-auto-field">
                            <option value="">{{ __('quran_attendance.present') }}</option>
                            @foreach ($reasons as $reason)
                                <option value="{{ $reason->id }}">{{ $reason->reason_name }}</option>
                            @endforeach
                        </select>

                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bulk-apply="[data-attendance-field]"
                                data-bulk-from="#bulkReason">
                            <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                            <span>{{ __('quran_attendance.apply_to_all') }}</span>
                        </button>
                    </span>
                </div>

                <x-table class="attendance-sheet"
                         :stack="false"
                         sticky
                         data-attendance-sheet
                         data-attendance-present="#tallyPresent"
                         data-attendance-absent="#tallyAbsent"
                         data-attendance-bar="#tallyBar"
                         :label="__('quran_attendance.mark_attendance')">
                    <thead>
                        <tr>
                            <th scope="col" class="col-num">#</th>
                            <th scope="col">{{ __('quran_attendance.employee') }}</th>
                            <th scope="col" style="width: 14rem;">{{ __('quran_attendance.status') }}</th>
                            <th scope="col">{{ __('quran_attendance.remarks') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($members as $member)
                            @php($existing = $existingAttendance->get($member->id))
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

                                <td>
                                    <label class="visually-hidden" for="attendance_{{ $member->id }}">
                                        {{ __('quran_attendance.status') }} — {{ $member->employee_name }}
                                    </label>
                                    <select name="attendance[{{ $member->id }}]"
                                            id="attendance_{{ $member->id }}"
                                            class="form-select form-select-sm"
                                            data-attendance-field>
                                        <option value="">{{ __('quran_attendance.present') }}</option>
                                        @foreach ($reasons as $reason)
                                            <option value="{{ $reason->id }}" @selected($existing?->attendance_reason_id == $reason->id)>
                                                {{ $reason->reason_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <label class="visually-hidden" for="remarks_{{ $member->id }}">
                                        {{ __('quran_attendance.remarks') }} — {{ $member->employee_name }}
                                    </label>
                                    <input type="text"
                                           name="remarks[{{ $member->id }}]"
                                           id="remarks_{{ $member->id }}"
                                           class="form-control form-control-sm"
                                           value="{{ $existing?->remarks ?? '' }}"
                                           placeholder="{{ __('quran_attendance.optional_remarks') }}"
                                           maxlength="500">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </x-card>

            <x-form.actions :submit="__('quran_attendance.save_attendance')"
                            :cancel-url="route('quran-attendance.index')"
                            :hint="__('quran_attendance.save_hint')" />
        </form>
    @endif

@elseif ($selectedClass && $members->isEmpty())
    <x-card>
        <x-empty-state icon="bi-person-x"
                       :title="__('quran_attendance.no_members_in_class_title')"
                       :text="__('quran_attendance.no_members_in_class')">
            @can('update', $selectedClass)
                <a href="{{ route('quran-classes.members.index', $selectedClass) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span>{{ __('quran_classes.manage_members') }}</span>
                </a>
            @endcan
        </x-empty-state>
    </x-card>
@else
    <x-card>
        <x-empty-state icon="bi-clipboard-check"
                       :title="__('quran_attendance.choose_class_title')"
                       :text="__('quran_attendance.choose_class_text')" />
    </x-card>
@endif
@endsection
