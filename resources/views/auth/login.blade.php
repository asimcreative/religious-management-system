@extends('layouts.auth')

@section('title', __('auth.login'))

@section('content')
    <h5 class="card-title text-center mb-4">{{ __('auth.login') }}</h5>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('auth.email') }}</label>
            <input type="email"
                   id="email"
                   name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">{{ __('auth.password') }}</label>
            <input type="password"
                   id="password"
                   name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required
                   autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember Me --}}
        <div class="mb-3 form-check">
            <input type="checkbox"
                   id="remember"
                   name="remember"
                   class="form-check-input"
                   value="1"
                   {{ old('remember') ? 'checked' : '' }}>
            <label for="remember" class="form-check-label">{{ __('auth.remember_me') }}</label>
        </div>

        {{-- Submit --}}
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                {{ __('auth.login') }}
            </button>
        </div>

        {{-- Forgot Password --}}
        <div class="text-center">
            <a href="{{ route('password.request') }}" class="text-decoration-none small">
                {{ __('auth.forgot_password') }}
            </a>
        </div>
    </form>
@endsection
