@extends('layouts.app')

@section('title', __('masters.add_new') . ' ' . __('masters.branch'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.add_new') }} {{ __('masters.branch') }}</h4>
    <a href="{{ route('masters.branches.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('masters.back') }}</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('masters.branches.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('masters.branch_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror"
                           value="{{ old('branch_name') }}" required>
                    @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('masters.phone') }}</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('masters.address') }}</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                <a href="{{ route('masters.branches.index') }}" class="btn btn-outline-secondary">{{ __('masters.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
