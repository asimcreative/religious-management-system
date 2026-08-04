@extends('layouts.app')

@section('title', __('jamaats.create_jamaat'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('jamaats.create_jamaat') }}</h4>
    <a href="{{ route('jamaats.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('jamaats.back_to_list') }}
    </a>
</div>

<form method="POST" action="{{ route('jamaats.store') }}">
    @csrf

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('jamaats.jamaat_info') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="jamaat_number" class="form-label">{{ __('jamaats.jamaat_number') }} <span class="text-danger">*</span></label>
                    <input type="text" id="jamaat_number" name="jamaat_number" class="form-control @error('jamaat_number') is-invalid @enderror"
                           value="{{ old('jamaat_number') }}" required>
                    @error('jamaat_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="jamaat_name" class="form-label">{{ __('jamaats.jamaat_name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="jamaat_name" name="jamaat_name" class="form-control @error('jamaat_name') is-invalid @enderror"
                           value="{{ old('jamaat_name') }}" required>
                    @error('jamaat_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">{{ __('jamaats.status') }} <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach(App\Enums\Status::cases() as $status)
                            <option value="{{ $status->value }}" {{ old('status', 1) == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="branch_id" class="form-label">{{ __('jamaats.branch') }} <span class="text-danger">*</span></label>
                    <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        <option value="">{{ __('jamaats.select') }}</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" {{ old('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">{{ __('jamaats.leadership') }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="leader_id" class="form-label">{{ __('jamaats.leader') }} <span class="text-danger">*</span></label>
                    <select id="leader_id" name="leader_id" class="form-select @error('leader_id') is-invalid @enderror" required>
                        <option value="">{{ __('jamaats.select') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('leader_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->employee_name }} ({{ $employee->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('leader_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="vice_leader_id" class="form-label">{{ __('jamaats.vice_leader') }}</label>
                    <select id="vice_leader_id" name="vice_leader_id" class="form-select @error('vice_leader_id') is-invalid @enderror">
                        <option value="">{{ __('jamaats.select') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('vice_leader_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->employee_name }} ({{ $employee->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('vice_leader_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> {{ __('jamaats.save') }}
        </button>
        <a href="{{ route('jamaats.index') }}" class="btn btn-outline-secondary">{{ __('jamaats.cancel') }}</a>
    </div>
</form>
@endsection
