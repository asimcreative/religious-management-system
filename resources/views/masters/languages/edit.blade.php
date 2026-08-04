@extends('layouts.app')

@section('title', __('masters.edit') . ' ' . __('masters.language'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.edit') }} {{ __('masters.language') }}</h4>
    <a href="{{ route('masters.languages.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('masters.back') }}</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('masters.languages.update', $language) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('masters.language_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="language_name" class="form-control @error('language_name') is-invalid @enderror"
                           value="{{ old('language_name', $language->language_name) }}" required>
                    @error('language_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('masters.native_name') }}</label>
                    <input type="text" name="native_name" class="form-control @error('native_name') is-invalid @enderror"
                           value="{{ old('native_name', $language->native_name) }}">
                    @error('native_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('masters.locale') }} <span class="text-danger">*</span></label>
                    <input type="text" name="locale" class="form-control @error('locale') is-invalid @enderror"
                           value="{{ old('locale', $language->locale) }}" placeholder="en" maxlength="10" required>
                    @error('locale') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('masters.direction') }} <span class="text-danger">*</span></label>
                    <select name="direction" class="form-select @error('direction') is-invalid @enderror" required>
                        <option value="ltr" {{ old('direction', $language->direction) === 'ltr' ? 'selected' : '' }}>{{ __('masters.ltr') }}</option>
                        <option value="rtl" {{ old('direction', $language->direction) === 'rtl' ? 'selected' : '' }}>{{ __('masters.rtl') }}</option>
                    </select>
                    @error('direction') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('masters.status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" {{ old('status', $language->status->value) == 1 ? 'selected' : '' }}>{{ __('masters.active') }}</option>
                        <option value="0" {{ old('status', $language->status->value) == 0 ? 'selected' : '' }}>{{ __('masters.inactive') }}</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ __('masters.save') }}</button>
                <a href="{{ route('masters.languages.index') }}" class="btn btn-outline-secondary">{{ __('masters.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
