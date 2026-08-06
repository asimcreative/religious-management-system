@extends('layouts.auth')

@section('title', __('auth.login'))

@section('content')
    <h1 class="rams-auth__title">{{ __('ui.login_heading') }}</h1>
    <p class="rams-auth__sub">{{ __('ui.login_sub') }}</p>

    {{-- Authentication failures are surfaced once, at the top, where the eye
         lands after a failed submit — and announced to screen readers. --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert" aria-live="assertive">
            <i class="bi bi-exclamation-octagon-fill alert__icon" aria-hidden="true"></i>
            <div class="alert__body">{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <x-form.input
            name="email"
            type="email"
            :label="__('auth.email')"
            icon="bi-envelope"
            placeholder="name@example.com"
            required
            autofocus
            autocomplete="username email"
            inputmode="email"
        />

        <x-form.password
            name="password"
            :label="__('auth.password')"
            required
            autocomplete="current-password"
        />

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <label class="form-check mb-0 cursor-pointer">
                <input type="checkbox" id="remember" name="remember" class="form-check-input" value="1"
                       {{ old('remember') ? 'checked' : '' }}>
                <span class="form-check-label fs-md">{{ __('auth.remember_me') }}</span>
            </label>

            <a href="{{ route('password.request') }}" class="fs-sm fw-medium">
                {{ __('auth.forgot_password') }}
            </a>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                <span>{{ __('auth.login') }}</span>
            </button>
        </div>
    </form>

    <p class="text-center fs-xs text-subtle mt-4 mb-0">
        <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>{{ __('ui.login_security_note') }}
    </p>
@endsection
