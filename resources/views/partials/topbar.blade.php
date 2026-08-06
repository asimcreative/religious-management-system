{{--
    Application topbar: navigation controls, wayfinding, quick jump, theme,
    notifications and the account menu.

    Only scalar attributes of the authenticated user are read — never a
    relationship — so the shell stays lazy-load safe.
--}}
@php
    $user = auth()->user();
    $unread = $unreadNotificationCount ?? 0;
@endphp

<header class="rams-topbar no-print">

    {{-- Drawer toggle (mobile / tablet) --}}
    <button type="button"
            class="rams-topbar__icon-btn d-lg-none"
            data-sidebar-open
            aria-controls="ramsSidebar"
            aria-expanded="false"
            aria-label="{{ __('ui.toggle_menu') }}">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    {{-- Breadcrumb trail --}}
    <div class="rams-topbar__crumbs">
        <nav aria-label="{{ __('ui.breadcrumb') }}">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline ms-1">{{ __('dashboard.dashboard') }}</span>
                        <span class="d-sm-none visually-hidden">{{ __('dashboard.dashboard') }}</span>
                    </a>
                </li>
                @yield('breadcrumbs')
            </ol>
        </nav>
    </div>

    {{-- Quick jump --}}
    <button type="button"
            class="rams-quicknav-btn"
            data-bs-toggle="modal"
            data-bs-target="#ramsQuickNav">
        <i class="bi bi-search" aria-hidden="true"></i>
        <span>{{ __('ui.quick_jump') }}</span>
        <kbd>{{ __('ui.shortcut_k') }}</kbd>
    </button>

    {{-- Theme --}}
    <button type="button"
            class="rams-topbar__icon-btn"
            data-theme-toggle
            aria-pressed="false"
            aria-label="{{ __('ui.switch_to_dark') }}"
            title="{{ __('ui.switch_to_dark') }}"
            data-label-dark="{{ __('ui.switch_to_dark') }}"
            data-label-light="{{ __('ui.switch_to_light') }}">
        <i class="bi bi-moon-stars" aria-hidden="true"></i>
    </button>

    {{-- Notifications --}}
    @can('notification.view')
        <a href="{{ route('notifications.index') }}"
           class="rams-topbar__icon-btn"
           title="{{ __('notifications.notifications') }}"
           aria-label="{{ $unread > 0 ? __('notifications.unread_count', ['count' => $unread]) : __('notifications.notifications') }}">
            <i class="bi bi-bell" aria-hidden="true"></i>
            @if ($unread > 0)
                <span class="rams-dot" data-unread-count aria-hidden="true">{{ $unread }}</span>
            @endif
        </a>
    @endcan

    {{-- Account --}}
    <div class="dropdown">
        <button type="button"
                class="rams-userchip"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-label="{{ __('ui.account_menu') }}">
            <x-avatar :name="$user?->name" />
            <span class="rams-userchip__meta">
                <strong class="truncate">{{ $user?->name }}</strong>
                <span class="truncate">{{ $user?->email }}</span>
            </span>
            <i class="bi bi-chevron-down fs-xs text-soft d-none d-md-inline" aria-hidden="true"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 15rem;">
            <li class="px-2 pt-1 pb-2 border-bottom border-subtle mb-1">
                <div class="fw-semibold fs-md truncate">{{ $user?->name }}</div>
                <div class="fs-xs text-soft truncate">{{ $user?->email }}</div>
            </li>

            <li>
                <a class="dropdown-item" href="{{ route('password.change.form') }}">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    {{ __('auth.change_password') }}
                </a>
            </li>

            <li>
                <button type="button" class="dropdown-item" data-theme-toggle>
                    <i class="bi bi-circle-half" aria-hidden="true"></i>
                    {{ __('ui.toggle_theme') }}
                </button>
            </li>

            <li>
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#ramsQuickNav">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    {{ __('ui.quick_jump') }}
                </button>
            </li>

            <li><hr class="dropdown-divider"></li>

            <x-locale-switcher />

            <li><hr class="dropdown-divider"></li>

            {{-- Plain button, not a submit: see partials/shell-forms. --}}
            <li>
                <button type="button" class="dropdown-item is-danger" data-shell-submit="#ramsLogoutForm">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    {{ __('auth.logout') }}
                </button>

                <noscript>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item is-danger">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            {{ __('auth.logout') }}
                        </button>
                    </form>
                </noscript>
            </li>
        </ul>
    </div>
</header>
