@extends('layouts.auth')

@section('title', __('auth.reset_password'))

@section('content')
    <h1 class="rams-auth__title">{{ __('auth.reset_password') }}</h1>
    <p class="rams-auth__sub">{{ __('ui.reset_password_sub') }}</p>

    <x-form.error-summary />

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-form.input
            name="email"
            type="email"
            :label="__('auth.email')"
            :value="$email"
            icon="bi-envelope"
            required
            autocomplete="username email"
            inputmode="email"
        />

        <x-form.password
            name="password"
            :label="__('auth.new_password')"
            :help="__('auth.password_requirements')"
            autocomplete="new-password"
            meter
            required
            autofocus
        />

        <x-form.password
            name="password_confirmation"
            :label="__('auth.confirm_password')"
            autocomplete="new-password"
            required
        />

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span>{{ __('auth.reset_password') }}</span>
            </button>
        </div>
    </form>

    <p class="text-center mt-4 mb-0">
        <a href="{{ route('login') }}" class="fs-sm text-soft">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('auth.back_to_login') }}
        </a>
    </p>
@endsection
