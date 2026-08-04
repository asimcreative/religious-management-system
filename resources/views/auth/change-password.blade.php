@extends('layouts.app')

@section('title', __('auth.change_password'))

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('auth.change_password') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.change') }}">
                        @csrf

                        {{-- Current Password --}}
                        <div class="mb-3">
                            <label for="current_password" class="form-label">{{ __('auth.current_password') }}</label>
                            <input type="password"
                                   id="current_password"
                                   name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   required
                                   autocomplete="current-password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('auth.new_password') }}</label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required
                                   autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('auth.password_requirements') }}</div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('auth.confirm_password') }}</label>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   class="form-control"
                                   required
                                   autocomplete="new-password">
                        </div>

                        {{-- Submit --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key me-1"></i>
                                {{ __('auth.change_password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
