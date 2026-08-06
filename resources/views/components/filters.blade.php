{{--
    Filter bar. Always a plain GET form so filters stay bookmarkable,
    shareable and back-button friendly.

    `active` receives the label => reset-url map of currently applied filters,
    rendered as removable chips so the user can always see and undo what is
    narrowing their results.
--}}
@props([
    'action' => null,
    'active' => [],
    'resetUrl' => null,
])

<form method="GET" @if ($action) action="{{ $action }}" @endif class="filter-bar no-print" role="search">
    <div class="filter-bar__row">
        {{ $slot }}

        <div class="filter-bar__actions">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <span>{{ __('ui.apply') }}</span>
            </button>

            @if ($resetUrl)
                <a href="{{ $resetUrl }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    <span>{{ __('ui.reset') }}</span>
                </a>
            @endif
        </div>
    </div>
</form>

@if (! empty($active))
    <div class="filter-chips">
        <span class="filter-chips__label">{{ __('ui.active_filters') }}</span>

        @foreach ($active as $label => $removeUrl)
            <a href="{{ $removeUrl }}" class="chip" title="{{ __('ui.remove_filter') }}">
                <span>{{ $label }}</span>
                <i class="bi bi-x-lg" aria-hidden="true"></i>
                <span class="visually-hidden">{{ __('ui.remove_filter') }}</span>
            </a>
        @endforeach

        @if ($resetUrl && count($active) > 1)
            <a href="{{ $resetUrl }}" class="fs-xs text-soft ms-1">{{ __('ui.clear_all') }}</a>
        @endif
    </div>
@endif
