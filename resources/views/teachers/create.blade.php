@extends('layouts.app')

@section('title', __('teachers.create_teacher'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('teachers.create_teacher') }}</h4>
    <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('teachers.back_to_list') }}
    </a>
</div>

<form method="POST" action="{{ route('teachers.store') }}">
    @csrf

    {{-- Teacher Information --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('teachers.teacher_info') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="teacher_code" class="form-label">{{ __('teachers.teacher_code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="teacher_code" id="teacher_code" class="form-control @error('teacher_code') is-invalid @enderror"
                           value="{{ old('teacher_code') }}" required>
                    @error('teacher_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="employee_id" class="form-label">{{ __('teachers.select_employee') }} <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        <option value="">{{ __('teachers.select') }}</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ (int) old('employee_id') === $emp->id ? 'selected' : '' }}>
                                {{ $emp->employee_code }} — {{ $emp->employee_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">{{ __('teachers.status') }} <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach(App\Enums\Status::cases() as $status)
                            <option value="{{ $status->value }}" {{ (int) old('status', 1) === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Branch Assignment --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('teachers.assign_branches') }} <span class="text-danger">*</span></h6>
        </div>
        <div class="card-body">
            @error('branch_ids') <div class="alert alert-danger py-1">{{ $message }}</div> @enderror
            <div class="row g-2">
                @foreach($branches as $id => $name)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="branch_ids[]" value="{{ $id }}"
                                   id="branch_{{ $id }}" {{ in_array($id, old('branch_ids', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="branch_{{ $id }}">{{ $name }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div class="card mb-3">
        <div class="card-body">
            <label for="notes" class="form-label">{{ __('teachers.notes') }}</label>
            <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> {{ __('teachers.save') }}
        </button>
        <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary">{{ __('teachers.cancel') }}</a>
    </div>
</form>
@endsection
