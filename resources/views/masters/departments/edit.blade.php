@extends('layouts.app')

@section('title', __('masters.edit') . ' ' . __('masters.department'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.edit') }} {{ __('masters.department') }}</h4>
    <a href="{{ route('masters.departments.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('masters.back') }}</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('masters.departments.update', $department) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('masters.department_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="department_name" class="form-control @error('department_name') is-invalid @enderror"
                           value="{{ old('department_name', $department->department_name) }}" required>
                    @error('department_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('masters.status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" {{ old('status', $department->status->value) == 1 ? 'selected' : '' }}>{{ __('masters.active') }}</option>
                        <option value="0" {{ old('status', $department->status->value) == 0 ? 'selected' : '' }}>{{ __('masters.inactive') }}</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ __('masters.save') }}</button>
                <a href="{{ route('masters.departments.index') }}" class="btn btn-outline-secondary">{{ __('masters.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
