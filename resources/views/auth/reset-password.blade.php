@extends('layouts.auth')

@section('title', __('auth.reset_password'))

@section('content')
    <h5 class="card-title text-center mb-4">{{ __('auth.reset_password') }}</h5>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('auth.email') }}</label>
            <input type="email"
                   id="email"
                   name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $email) }}"
                   required
                   autocomplete="email">
            @error('email')
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
                {{ __('auth.reset_password') }}
            </button>
        </div>
    </form>
@endsection
