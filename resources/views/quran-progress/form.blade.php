@extends('layouts.app')

@section('title', __('quran_progress.update_progress'))

@php($isEdit = (bool) $quranProgress)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-progress.index') }}">{{ __('quran_progress.quran_progress') }}</a></li>
    @if ($isEdit)
        <li class="breadcrumb-item"><a href="{{ route('quran-progress.show', $quranProgress) }}">{{ $quranProgress->employee?->employee_name }}</a></li>
    @endif
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_progress.update_progress') }}</li>
@endsection

@section('content')
<x-page-header :title="__('quran_progress.update_progress')"
               :subtitle="$isEdit ? ($quranProgress->employee?->employee_name ?? '') : __('quran_progress.create_subtitle')"
               icon="bi-graph-up-arrow">
    <x-slot:actions>
        <a href="{{ $isEdit ? route('quran-progress.show', $quranProgress) : route('quran-progress.index') }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('quran_progress.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST"
      action="{{ $isEdit ? route('quran-progress.update', $quranProgress) : route('quran-progress.store') }}"
      novalidate data-guard>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <x-card :title="__('quran_progress.progress_info')" icon="bi-person-badge" class="mb-3">
                <div class="row">
                    <div class="col-12 col-md-6">
                        @if ($isEdit)
                            {{-- The student cannot be reassigned once progress exists; the
                                 record is shown read-only and posted as a hidden value. --}}
                            <input type="hidden" name="employee_id" value="{{ $quranProgress->employee_id }}">
                            <label for="employee_display" class="form-label">{{ __('quran_progress.employee') }}</label>
                            <div class="input-icon mb-3">
                                <i class="bi bi-lock" aria-hidden="true"></i>
                                <input type="text" id="employee_display" class="form-control" readonly
                                       value="{{ $quranProgress->employee?->employee_name }} ({{ $quranProgress->employee?->employee_code }})">
                            </div>
                        @else
                            <x-form.select name="employee_id"
                                           :label="__('quran_progress.employee')"
                                           :selected="$selectedEmployeeId ?? null"
                                           :placeholder="__('quran_progress.select_employee')"
                                           required>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}"
                                        @selected((int) old('employee_id', $selectedEmployeeId ?? 0) === $employee->id)>
                                        {{ $employee->employee_name }} ({{ $employee->employee_code }})
                                    </option>
                                @endforeach
                            </x-form.select>
                        @endif
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.select name="teacher_id"
                                       :label="__('quran_progress.teacher')"
                                       :selected="$quranProgress?->teacher_id"
                                       :placeholder="__('quran_progress.select')"
                                       :options="$teachers"
                                       required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.select name="quran_department_id"
                                       :label="__('quran_progress.department')"
                                       :selected="$quranProgress?->quran_department_id"
                                       :placeholder="__('quran_progress.select')"
                                       :options="$quranDepartments"
                                       required />
                    </div>

                    <div class="col-12 col-md-6">
                        <x-form.select name="quran_status_id"
                                       :label="__('quran_progress.quran_status')"
                                       :selected="$quranProgress?->quran_status_id"
                                       :placeholder="__('quran_progress.select')"
                                       :options="$quranStatuses"
                                       required />
                    </div>
                </div>
            </x-card>

            <x-card :title="__('quran_progress.current_position')" icon="bi-bookmark">
                <p class="fs-sm text-soft mt-n1 mb-3">{{ __('quran_progress.position_hint') }}</p>

                <div class="row">
                    <div class="col-6 col-md-3">
                        <x-form.input name="current_lesson"
                                      :label="__('quran_progress.lesson')"
                                      :value="$quranProgress?->current_lesson"
                                      maxlength="100"
                                      optional />
                    </div>
                    <div class="col-6 col-md-3">
                        <x-form.input name="current_surah"
                                      :label="__('quran_progress.surah')"
                                      :value="$quranProgress?->current_surah"
                                      maxlength="100"
                                      optional />
                    </div>
                    <div class="col-6 col-md-3">
                        <x-form.input name="current_sipara"
                                      type="number"
                                      :label="__('quran_progress.sipara')"
                                      :value="$quranProgress?->current_sipara"
                                      min="1" max="30" step="1"
                                      inputmode="numeric"
                                      optional />
                    </div>
                    <div class="col-6 col-md-3">
                        <x-form.input name="current_page"
                                      type="number"
                                      :label="__('quran_progress.page')"
                                      :value="$quranProgress?->current_page"
                                      min="1" max="604" step="1"
                                      inputmode="numeric"
                                      optional />
                    </div>

                    <div class="col-12 col-md-4">
                        <x-form.input name="completion_percentage"
                                      type="number"
                                      :label="__('quran_progress.completion').' (%)'"
                                      :value="$quranProgress?->completion_percentage ?? 0"
                                      :help="__('quran_progress.completion_help')"
                                      min="0" max="100" step="0.01"
                                      inputmode="decimal"
                                      required />
                    </div>

                    <div class="col-12 col-md-8">
                        <x-form.input name="remarks"
                                      :label="__('quran_progress.remarks')"
                                      :value="$quranProgress?->remarks"
                                      maxlength="5000"
                                      optional />
                    </div>
                </div>
            </x-card>
        </div>

        <div class="col-12 col-xl-4">
            <x-card :title="__('quran_progress.guidance_title')" icon="bi-info-circle">
                <ul class="stack-sm list-unstyled mb-0 fs-md text-soft">
                    <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_progress.guidance_1') }}</li>
                    <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_progress.guidance_2') }}</li>
                    <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ __('quran_progress.guidance_3') }}</li>
                </ul>
            </x-card>
        </div>
    </div>

    <x-form.actions :submit="__('quran_progress.save')"
                    :cancel-url="$isEdit ? route('quran-progress.show', $quranProgress) : route('quran-progress.index')" />
</form>
@endsection
