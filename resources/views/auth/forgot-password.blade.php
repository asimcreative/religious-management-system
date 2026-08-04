@extends('layouts.auth')

@section('title', __('auth.forgot_password'))

@section('content')
    <h5 class="card-title text-center mb-3">{{ __('auth.forgot_password') }}</h5>
    <p class="text-muted text-center small mb-4">{{ __('auth.forgot_password_instructions') }}</p>

    <form method="POST" action="{{ route('password.email') }}">
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

        {{-- Submit --}}
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-envelope me-1"></i>
                {{ __('auth.send_reset_link') }}
            </button>
        </div>

        {{-- Back to Login --}}
        <div class="text-center">
            <a href="{{ route('login') }}" class="text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>
                {{ __('auth.back_to_login') }}
            </a>
        </div>
    </form>
@endsection
