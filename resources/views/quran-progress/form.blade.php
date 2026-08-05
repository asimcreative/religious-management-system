@extends('layouts.app')

@section('title', $quranProgress ? __('quran_progress.update_progress') : __('quran_progress.update_progress'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('quran_progress.update_progress') }}</h4>
    <a href="{{ route('quran-progress.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('quran_progress.back_to_list') }}
    </a>
</div>

<form method="POST" action="{{ $quranProgress ? route('quran-progress.update', $quranProgress) : route('quran-progress.store') }}">
    @csrf
    @if($quranProgress)
        @method('PUT')
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('quran_progress.progress_info') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="employee_id" class="form-label">{{ __('quran_progress.employee') }} <span class="text-danger">*</span></label>
                    @if($quranProgress)
                        <input type="hidden" name="employee_id" value="{{ $quranProgress->employee_id }}">
                        <input type="text" class="form-control" value="{{ $quranProgress->employee?->employee_name }} ({{ $quranProgress->employee?->employee_code }})" readonly>
                    @else
                    <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        <option value="">{{ __('quran_progress.select_employee') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id', $selectedEmployeeId ?? $quranProgress?->employee_id) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->employee_name }} ({{ $employee->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @endif
                    @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="teacher_id" class="form-label">{{ __('quran_progress.teacher') }} <span class="text-danger">*</span></label>
                    <select id="teacher_id" name="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror" required>
                        <option value="">{{ __('quran_progress.select') }}</option>
                        @foreach($teachers as $id => $name)
                            <option value="{{ $id }}" {{ old('teacher_id', $quranProgress?->teacher_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('teacher_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="quran_department_id" class="form-label">{{ __('quran_progress.department') }} <span class="text-danger">*</span></label>
                    <select id="quran_department_id" name="quran_department_id" class="form-select @error('quran_department_id') is-invalid @enderror" required>
                        <option value="">{{ __('quran_progress.select') }}</option>
                        @foreach($quranDepartments as $id => $name)
                            <option value="{{ $id }}" {{ old('quran_department_id', $quranProgress?->quran_department_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('quran_department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="quran_status_id" class="form-label">{{ __('quran_progress.quran_status') }} <span class="text-danger">*</span></label>
                    <select id="quran_status_id" name="quran_status_id" class="form-select @error('quran_status_id') is-invalid @enderror" required>
                        <option value="">{{ __('quran_progress.select') }}</option>
                        @foreach($quranStatuses as $id => $name)
                            <option value="{{ $id }}" {{ old('quran_status_id', $quranProgress?->quran_status_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('quran_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('quran_progress.current_position') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="current_lesson" class="form-label">{{ __('quran_progress.lesson') }}</label>
                    <input type="text" id="current_lesson" name="current_lesson" class="form-control @error('current_lesson') is-invalid @enderror"
                           value="{{ old('current_lesson', $quranProgress?->current_lesson) }}" maxlength="100">
                    @error('current_lesson') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="current_surah" class="form-label">{{ __('quran_progress.surah') }}</label>
                    <input type="text" id="current_surah" name="current_surah" class="form-control @error('current_surah') is-invalid @enderror"
                           value="{{ old('current_surah', $quranProgress?->current_surah) }}" maxlength="100">
                    @error('current_surah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="current_sipara" class="form-label">{{ __('quran_progress.sipara') }}</label>
                    <input type="number" id="current_sipara" name="current_sipara" class="form-control @error('current_sipara') is-invalid @enderror"
                           value="{{ old('current_sipara', $quranProgress?->current_sipara) }}" min="1" max="30">
                    @error('current_sipara') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="current_page" class="form-label">{{ __('quran_progress.page') }}</label>
                    <input type="number" id="current_page" name="current_page" class="form-control @error('current_page') is-invalid @enderror"
                           value="{{ old('current_page', $quranProgress?->current_page) }}" min="1" max="604">
                    @error('current_page') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="completion_percentage" class="form-label">{{ __('quran_progress.completion') }} (%) <span class="text-danger">*</span></label>
                    <input type="number" id="completion_percentage" name="completion_percentage" class="form-control @error('completion_percentage') is-invalid @enderror"
                           value="{{ old('completion_percentage', $quranProgress?->completion_percentage ?? 0) }}" min="0" max="100" step="0.01" required>
                    @error('completion_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-8">
                    <label for="remarks" class="form-label">{{ __('quran_progress.remarks') }}</label>
                    <input type="text" id="remarks" name="remarks" class="form-control @error('remarks') is-invalid @enderror"
                           value="{{ old('remarks', $quranProgress?->remarks) }}" maxlength="5000">
                    @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> {{ __('quran_progress.save') }}
        </button>
        <a href="{{ route('quran-progress.index') }}" class="btn btn-outline-secondary">{{ __('quran_progress.cancel') }}</a>
    </div>
</form>
@endsection
