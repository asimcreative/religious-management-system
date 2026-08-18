<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Http\Middleware\SetLocale::direction() }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0F766E">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('auth.login')) — {{ config('app.name', 'RAMS') }}</title>

    @include('partials.theme-boot')

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>

<div class="rams-auth">

    {{-- ── Brand panel (desktop) ───────────────────────────────────────── --}}
    <aside class="rams-auth__aside">
        <div class="rams-auth__brand">
            <span class="rams-sidebar__logo" aria-hidden="true"><i class="bi bi-moon-stars-fill"></i></span>
            <span>{{ config('app.name', 'RAMS') }}</span>
        </div>

        <div>
            <h1 class="rams-auth__headline">{{ __('ui.auth_headline') }}</h1>
            <p class="rams-auth__lede">{{ __('ui.auth_lede') }}</p>

            <ul class="rams-auth__points">
                <li><i class="bi bi-people" aria-hidden="true"></i>{{ __('ui.auth_point_people') }}</li>
                <li><i class="bi bi-book" aria-hidden="true"></i>{{ __('ui.auth_point_quran') }}</li>
                <li><i class="bi bi-calendar-check" aria-hidden="true"></i>{{ __('ui.auth_point_salah') }}</li>
                <li><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('ui.auth_point_security') }}</li>
            </ul>
        </div>

        <p class="rams-auth__legal mb-0">
            &copy; {{ date('Y') }} {{ config('app.name', 'RAMS') }} — {{ __('auth.app_tagline') }}
        </p>
    </aside>

    {{-- ── Form panel ──────────────────────────────────────────────────── --}}
    <main class="rams-auth__panel" id="main-content">
        <div class="rams-auth__card">

            <div class="rams-auth__mobile-brand">
                <span class="rams-sidebar__logo" aria-hidden="true"><i class="bi bi-moon-stars-fill"></i></span>
                <span class="fw-semibold fs-5">{{ config('app.name', 'RAMS') }}</span>
            </div>

            {{-- Session feedback --}}
            @foreach (['status' => 'success', 'success' => 'success', 'error' => 'danger'] as $key => $tone)
                @if (session($key))
                    <div class="alert alert-{{ $tone }} alert-dismissible fade show"
                         role="{{ $tone === 'danger' ? 'alert' : 'status' }}">
                        <i class="bi {{ $tone === 'danger' ? 'bi-exclamation-octagon-fill' : 'bi-check-circle-fill' }} alert__icon" aria-hidden="true"></i>
                        <div class="alert__body">{{ session($key) }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.dismiss') }}"></button>
                    </div>
                @endif
            @endforeach

            @yield('content')

            {{-- Language is offered before sign-in: a user who cannot read the
                 form cannot reach the setting that would fix it. --}}
            <div class="d-flex justify-content-center mt-4">
                <x-locale-switcher variant="inline" />
            </div>

            <p class="text-center fs-xs text-subtle mt-3 mb-0 d-lg-none">
                &copy; {{ date('Y') }} {{ config('app.name', 'RAMS') }}
            </p>
        </div>
    </main>
</div>

</body>
</html>
