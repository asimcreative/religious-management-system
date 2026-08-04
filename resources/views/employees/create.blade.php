@extends('layouts.app')

@section('title', __('employees.create_employee'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('employees.create_employee') }}</h4>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('employees.back_to_list') }}
    </a>
</div>

<form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- Personal Information --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('employees.personal_info') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="employee_code" class="form-label">{{ __('employees.employee_code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="employee_code" id="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                           value="{{ old('employee_code') }}" required>
                    @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="employee_name" class="form-label">{{ __('employees.employee_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="employee_name" id="employee_name" class="form-control @error('employee_name') is-invalid @enderror"
                           value="{{ old('employee_name') }}" required>
                    @error('employee_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="cnic" class="form-label">{{ __('employees.cnic') }}</label>
                    <input type="text" name="cnic" id="cnic" class="form-control @error('cnic') is-invalid @enderror"
                           value="{{ old('cnic') }}" placeholder="XXXXX-XXXXXXX-X">
                    @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="mobile" class="form-label">{{ __('employees.mobile') }}</label>
                    <input type="text" name="mobile" id="mobile" class="form-control @error('mobile') is-invalid @enderror"
                           value="{{ old('mobile') }}">
                    @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">{{ __('employees.email') }}</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="dob" class="form-label">{{ __('employees.dob') }}</label>
                    <input type="date" name="dob" id="dob" class="form-control @error('dob') is-invalid @enderror"
                           value="{{ old('dob') }}">
                    @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="gender" class="form-label">{{ __('employees.gender') }}</label>
                    <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">{{ __('employees.select') }}</option>
                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>{{ __('employees.male') }}</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>{{ __('employees.female') }}</option>
                    </select>
                    @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="photo" class="form-label">{{ __('employees.photo') }}</label>
                    <input type="file" name="photo" id="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                    @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Organization --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('employees.organization_info') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="branch_id" class="form-label">{{ __('employees.branch') }} <span class="text-danger">*</span></label>
                    <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        <option value="">{{ __('employees.select') }}</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" {{ (int) old('branch_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="department_id" class="form-label">{{ __('employees.department') }} <span class="text-danger">*</span></label>
                    <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                        <option value="">{{ __('employees.select') }}</option>
                        @foreach($departments as $id => $name)
                            <option value="{{ $id }}" {{ (int) old('department_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="designation_id" class="form-label">{{ __('employees.designation') }} <span class="text-danger">*</span></label>
                    <select name="designation_id" id="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                        <option value="">{{ __('employees.select') }}</option>
                        @foreach($designations as $id => $name)
                            <option value="{{ $id }}" {{ (int) old('designation_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('designation_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="employment_status" class="form-label">{{ __('employees.status') }} <span class="text-danger">*</span></label>
                    <select name="employment_status" id="employment_status" class="form-select @error('employment_status') is-invalid @enderror" required>
                        @foreach(App\Enums\Status::cases() as $status)
                            <option value="{{ $status->value }}" {{ (int) old('employment_status', 1) === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    @error('employment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Religious Information --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('employees.religious_info') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="quran_department_id" class="form-label">{{ __('employees.quran_department') }}</label>
                    <select name="quran_department_id" id="quran_department_id" class="form-select @error('quran_department_id') is-invalid @enderror">
                        <option value="">{{ __('employees.select') }}</option>
                        @foreach($quranDepartments as $id => $name)
                            <option value="{{ $id }}" {{ (int) old('quran_department_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('quran_department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="quran_status_id" class="form-label">{{ __('employees.quran_status') }}</label>
                    <select name="quran_status_id" id="quran_status_id" class="form-select @error('quran_status_id') is-invalid @enderror">
                        <option value="">{{ __('employees.select') }}</option>
                        @foreach($quranStatuses as $id => $name)
                            <option value="{{ $id }}" {{ (int) old('quran_status_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('quran_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div class="card mb-3">
        <div class="card-body">
            <label for="notes" class="form-label">{{ __('employees.notes') }}</label>
            <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> {{ __('employees.save') }}
        </button>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">{{ __('employees.cancel') }}</a>
    </div>
</form>
@endsection
