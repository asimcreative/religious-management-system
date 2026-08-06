<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-bs-theme="light"
      data-confirm-title="{{ __('ui.confirm_action') }}"
      data-confirm-accept="{{ __('ui.confirm') }}"
      data-confirm-cancel="{{ __('ui.cancel') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0F766E" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0C1524" media="(prefers-color-scheme: dark)">
    <meta name="description" content="{{ __('auth.app_tagline') }}">

    <title>@yield('title', __('dashboard.dashboard')) — {{ config('app.name', 'RAMS') }}</title>

    @include('partials.theme-boot')

    <style>
        /* Match the collapsed rail width before ui.js runs, so the shell
           does not visibly jump on load for users who collapsed the menu. */
        .rams-preload-collapsed .rams-app { --sidebar-w: var(--rams-sidebar-width-collapsed); }
    </style>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    @stack('head')
</head>
<body>

    <a class="skip-link" href="#main-content">{{ __('ui.skip_to_content') }}</a>

    <div class="rams-app" data-app-shell>

        @include('partials.sidebar')

        <div class="rams-backdrop" data-sidebar-backdrop aria-hidden="true"></div>

        <div class="rams-main">

            @include('partials.topbar')

            <main class="rams-content" id="main-content" tabindex="-1">
                <x-flash />

                @yield('content')
            </main>

            <footer class="rams-footer no-print">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'RAMS') }}</span>
                <span class="text-subtle">{{ __('auth.app_tagline') }}</span>
            </footer>
        </div>
    </div>

    @include('partials.quicknav')
    @include('partials.shell-forms')

    <div class="rams-toaster" role="region" aria-label="{{ __('ui.notifications_region') }}"></div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
