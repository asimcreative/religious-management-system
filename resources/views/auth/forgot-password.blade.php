@extends('layouts.auth')

@section('title', __('auth.forgot_password'))

@section('content')
    <a href="{{ route('login') }}" class="fs-sm text-soft d-inline-flex align-items-center gap-1 mb-3">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>{{ __('auth.back_to_login') }}
    </a>

    <h1 class="rams-auth__title">{{ __('auth.forgot_password') }}</h1>
    <p class="rams-auth__sub">{{ __('auth.forgot_password_instructions') }}</p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert" aria-live="assertive">
            <i class="bi bi-exclamation-octagon-fill alert__icon" aria-hidden="true"></i>
            <div class="alert__body">{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
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

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-send" aria-hidden="true"></i>
                <span>{{ __('auth.send_reset_link') }}</span>
            </button>
        </div>
    </form>

    <div class="alert alert-info mb-0">
        <i class="bi bi-info-circle-fill alert__icon" aria-hidden="true"></i>
        <div class="alert__body">{{ __('ui.reset_link_note') }}</div>
    </div>
@endsection
