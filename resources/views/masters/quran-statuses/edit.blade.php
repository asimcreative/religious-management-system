@extends('layouts.app')

@section('title', __('masters.edit') . ' ' . __('masters.quran_status'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.edit') }} {{ __('masters.quran_status') }}</h4>
    <a href="{{ route('masters.quran-statuses.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('masters.back') }}</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('masters.quran-statuses.update', $quranStatus) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('masters.status_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="status_name" class="form-control @error('status_name') is-invalid @enderror"
                           value="{{ old('status_name', $quranStatus->status_name) }}" required>
                    @error('status_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('masters.color') }}</label>
                    <input type="color" name="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                           value="{{ old('color', $quranStatus->color ?? '#6c757d') }}">
                    @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('masters.display_order') }} <span class="text-danger">*</span></label>
                    <input type="number" name="display_order" class="form-control @error('display_order') is-invalid @enderror"
                           value="{{ old('display_order', $quranStatus->display_order) }}" min="0" max="255" required>
                    @error('display_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('masters.icon') }}</label>
                    <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror"
                           value="{{ old('icon', $quranStatus->icon) }}" placeholder="bi bi-circle">
                    @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('masters.status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="1" {{ old('status', $quranStatus->status->value) == 1 ? 'selected' : '' }}>{{ __('masters.active') }}</option>
                        <option value="0" {{ old('status', $quranStatus->status->value) == 0 ? 'selected' : '' }}>{{ __('masters.inactive') }}</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('masters.description') }}</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $quranStatus->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ __('masters.save') }}</button>
                <a href="{{ route('masters.quran-statuses.index') }}" class="btn btn-outline-secondary">{{ __('masters.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
