@extends('layouts.app')

@section('title', __('quran_classes.edit_class'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('quran_classes.edit_class') }}</h4>
    <a href="{{ route('quran-classes.show', $quranClass) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('quran_classes.back_to_detail') }}
    </a>
</div>

<form method="POST" action="{{ route('quran-classes.update', $quranClass) }}">
    @csrf @method('PUT')

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('quran_classes.class_info') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="class_code" class="form-label">{{ __('quran_classes.class_code') }} <span class="text-danger">*</span></label>
                    <input type="text" id="class_code" name="class_code" class="form-control @error('class_code') is-invalid @enderror"
                           value="{{ old('class_code', $quranClass->class_code) }}" required>
                    @error('class_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="class_name" class="form-label">{{ __('quran_classes.class_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="class_name" name="class_name" class="form-control @error('class_name') is-invalid @enderror"
                           value="{{ old('class_name', $quranClass->class_name) }}" required>
                    @error('class_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">{{ __('quran_classes.status') }} <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach(App\Enums\Status::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('status', $quranClass->status->value) == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="teacher_id" class="form-label">{{ __('quran_classes.teacher') }} <span class="text-danger">*</span></label>
                    <select id="teacher_id" name="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror" required>
                        <option value="">{{ __('quran_classes.select') }}</option>
                        @foreach($teachers as $id => $name)
                            <option value="{{ $id }}" {{ old('teacher_id', $quranClass->teacher_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('teacher_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="branch_id" class="form-label">{{ __('quran_classes.branch') }} <span class="text-danger">*</span></label>
                    <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        <option value="">{{ __('quran_classes.select') }}</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" {{ old('branch_id', $quranClass->branch_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('quran_classes.schedule') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="start_time" class="form-label">{{ __('quran_classes.start_time') }}</label>
                    <input type="time" id="start_time" name="start_time" class="form-control @error('start_time') is-invalid @enderror"
                           value="{{ old('start_time', $quranClass->start_time ? \Carbon\Carbon::parse($quranClass->start_time)->format('H:i') : '') }}">
                    @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="end_time" class="form-label">{{ __('quran_classes.end_time') }}</label>
                    <input type="time" id="end_time" name="end_time" class="form-control @error('end_time') is-invalid @enderror"
                           value="{{ old('end_time', $quranClass->end_time ? \Carbon\Carbon::parse($quranClass->end_time)->format('H:i') : '') }}">
                    @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="max_strength" class="form-label">{{ __('quran_classes.max_strength') }} <span class="text-danger">*</span></label>
                    <input type="number" id="max_strength" name="max_strength" class="form-control @error('max_strength') is-invalid @enderror"
                           value="{{ old('max_strength', $quranClass->max_strength) }}" min="1" max="999" required>
                    @error('max_strength') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> {{ __('quran_classes.save') }}
        </button>
        <a href="{{ route('quran-classes.show', $quranClass) }}" class="btn btn-outline-secondary">{{ __('quran_classes.cancel') }}</a>
    </div>
</form>
@endsection
