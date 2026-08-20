@extends('layouts.app')

@section('title', __('quran_classes.admission_form').' — '.$employee->employee_name)

@php
    $age = $employee->dob?->age;
@endphp

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.index') }}">{{ __('quran_classes.quran_classes') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.members.index', $quranClass) }}">{{ $quranClass->class_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_classes.admission_form') }}</li>
@endsection

@section('content')
<x-page-header :title="__('quran_classes.admission_form')"
               :subtitle="__('quran_classes.admission_subtitle', ['name' => $employee->employee_name])"
               icon="bi-person-vcard">
    <x-slot:actions>
        <a href="{{ route('quran-classes.members.index', $quranClass) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('quran_classes.back_to_members') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST" action="{{ route('quran-classes.members.admission.store', [$quranClass, $employee]) }}" novalidate data-guard>
    @csrf

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            {{-- ── Employee Information — read-only, already known ─────────────── --}}
            <x-card :title="__('quran_classes.employee_information')" icon="bi-person" class="mb-3">
                <div class="row">
                    <div class="col-6 col-md-3">
                        <span class="form-label d-block">{{ __('employees.employee_name') }}</span>
                        <p class="fw-semibold text-strong mb-0">{{ $employee->employee_name }}</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="form-label d-block">{{ __('employees.employee_code') }}</span>
                        <p class="fw-semibold text-strong mb-0">{{ $employee->employee_code }}</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="form-label d-block">{{ __('quran_classes.branch') }}</span>
                        <p class="fw-semibold text-strong mb-0">{{ $employee->branch?->branch_name ?? '—' }}</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="form-label d-block">{{ __('employees.department') }}</span>
                        <p class="fw-semibold text-strong mb-0">{{ $employee->department?->department_name ?? '—' }}</p>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="form-label d-block">{{ __('employees.age') }}</span>
                        <p class="fw-semibold text-strong mb-0">{{ $age !== null ? __('employees.age_years', ['count' => $age]) : '—' }}</p>
                    </div>
                </div>
            </x-card>

            {{-- ── Quran Class Information ──────────────────────────────────────── --}}
            <x-card :title="__('quran_classes.quran_class_information')" icon="bi-book" class="mb-3">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <x-form.select name="quran_department_id"
                                       :label="__('quran_classes.quran_class_type')"
                                       :selected="old('quran_department_id', $progress?->quran_department_id)"
                                       :placeholder="__('quran_classes.select')"
                                       :options="$departments->pluck('department_name', 'id')"
                                       required />
                    </div>
                    <div class="col-12 col-md-6">
                        <span class="form-label d-block">{{ __('quran_classes.assigned_teacher') }}</span>
                        <p class="fw-semibold text-strong mb-0">{{ $quranClass->teacher?->getEmployeeName() ?? '—' }}</p>
                    </div>

                    <div class="col-12 col-md-4">
                        <x-form.select name="current_reading_level"
                                       :label="__('quran_classes.current_reading_level')"
                                       :selected="old('current_reading_level', $admission?->current_reading_level)"
                                       :placeholder="__('quran_classes.select')"
                                       :options="array_combine(range(1, 10), range(1, 10))"
                                       :help="__('quran_classes.current_reading_level_help')"
                                       required />
                    </div>
                    <div class="col-12 col-md-4">
                        <x-form.select name="previously_completed_quran"
                                       :label="__('quran_classes.previously_completed_quran')"
                                       :selected="old('previously_completed_quran', $admission ? (int) $admission->previously_completed_quran : null)"
                                       :options="['1' => __('ui.yes'), '0' => __('ui.no')]"
                                       required />
                    </div>
                    <div class="col-12 col-md-4">
                        <x-form.input name="admission_date"
                                      type="date"
                                      :label="__('quran_classes.admission_date')"
                                      :value="old('admission_date', $admission?->admission_date?->toDateString() ?? now()->toDateString())"
                                      max="{{ now()->toDateString() }}"
                                      required />
                    </div>

                    <div class="col-12 col-md-4">
                        <x-form.select name="classes_per_week"
                                       :label="__('quran_classes.classes_per_week')"
                                       :selected="old('classes_per_week', $admission?->classes_per_week)"
                                       :options="['5' => __('quran_classes.days_5'), '6' => __('quran_classes.days_6')]"
                                       required />
                    </div>
                </div>
            </x-card>

            {{-- ── Current Starting Point ───────────────────────────────────────── --}}
            <x-card :title="__('quran_classes.current_starting_point')" icon="bi-bookmark" class="mb-3">
                <p class="fs-sm text-soft mt-n1 mb-3">{{ __('quran_classes.current_starting_point_help') }}</p>
                <div class="row">
                    <div class="col-12 col-md-4">
                        <x-form.input name="current_lesson"
                                      :label="__('quran_classes.lesson_no')"
                                      :value="old('current_lesson', $progress?->current_lesson)"
                                      maxlength="100"
                                      optional />
                    </div>
                    <div class="col-12 col-md-4">
                        <x-form.input name="current_sipara"
                                      type="number"
                                      :label="__('quran_classes.juz_no')"
                                      :value="old('current_sipara', $progress?->current_sipara)"
                                      min="1" max="30" step="1"
                                      inputmode="numeric"
                                      optional />
                    </div>
                    <div class="col-12 col-md-4">
                        <x-form.input name="current_surah"
                                      :label="__('quran_classes.surah')"
                                      :value="old('current_surah', $progress?->current_surah)"
                                      maxlength="100"
                                      optional />
                    </div>
                </div>
            </x-card>

            {{-- ── Remarks ───────────────────────────────────────────────────────── --}}
            <x-card :title="__('quran_classes.remarks')" icon="bi-chat-left-text">
                <x-form.textarea name="remarks" :value="old('remarks', $admission?->remarks)" :rows="3" maxlength="5000" optional />
            </x-card>
        </div>

        <div class="col-12 col-xl-4">
            <x-card :title="__('quran_classes.admission_guidance_title')" icon="bi-info-circle">
                <ul class="stack-sm list-unstyled mb-0 fs-md text-soft">
                    <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_classes.admission_guidance_1') }}</li>
                    <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_classes.admission_guidance_2') }}</li>
                    <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_classes.admission_guidance_3') }}</li>
                </ul>
            </x-card>
        </div>
    </div>

    <x-form.actions :submit="__('quran_classes.save_admission')"
                    :cancel="__('quran_classes.skip_for_now')"
                    :cancel-url="route('quran-classes.members.index', $quranClass)" />
</form>
@endsection
