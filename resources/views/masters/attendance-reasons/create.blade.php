@extends('layouts.app')

@section('title', __('masters.add_new') . ' ' . __('masters.attendance_reason'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.add_new') }} {{ __('masters.attendance_reason') }}</h4>
    <a href="{{ route('masters.attendance-reasons.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('masters.back') }}</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('masters.attendance-reasons.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('masters.reason_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="reason_name" class="form-control @error('reason_name') is-invalid @enderror"
                           value="{{ old('reason_name') }}" required>
                    @error('reason_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('masters.color') }}</label>
                    <input type="color" name="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                           value="{{ old('color', '#6c757d') }}">
                    @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('masters.icon') }}</label>
                    <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror"
                           value="{{ old('icon') }}" placeholder="bi bi-check-circle">
                    @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="hidden" name="counts_as_absent" value="0">
                        <input class="form-check-input" type="checkbox" name="counts_as_absent" value="1" id="counts_as_absent"
                               {{ old('counts_as_absent') ? 'checked' : '' }}>
                        <label class="form-check-label" for="counts_as_absent">{{ __('masters.counts_as_absent') }}</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="hidden" name="counts_as_leave" value="0">
                        <input class="form-check-input" type="checkbox" name="counts_as_leave" value="1" id="counts_as_leave"
                               {{ old('counts_as_leave') ? 'checked' : '' }}>
                        <label class="form-check-label" for="counts_as_leave">{{ __('masters.counts_as_leave') }}</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('masters.status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>{{ __('masters.active') }}</option>
                        <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>{{ __('masters.inactive') }}</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ __('masters.save') }}</button>
                <a href="{{ route('masters.attendance-reasons.index') }}" class="btn btn-outline-secondary">{{ __('masters.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
