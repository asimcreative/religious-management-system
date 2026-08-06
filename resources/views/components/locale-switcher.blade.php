{{--
    Display-language switcher.

    A POST form per language: changing a stored preference must never happen on
    a GET. Rendered as a dropdown in the application shell and as inline buttons
    on the sign-in screens, where there is no shell to hang a menu off.

    Variants: 'menu' (dropdown items) | 'inline' (compact pill row)
--}}
@props(['variant' => 'menu'])

@php
    $current = app()->getLocale();
    $locales = \App\Http\Middleware\SetLocale::SUPPORTED_LOCALES;
@endphp

@if ($variant === 'inline')
    <div class="locale-inline" role="group" aria-label="{{ __('ui.change_language') }}">
        <i class="bi bi-translate" aria-hidden="true"></i>

        @foreach ($locales as $locale)
            @if ($locale === $current)
                <span class="locale-inline__item is-active" aria-current="true">{{ __('ui.locale_'.$locale) }}</span>
            @else
                <form method="POST" action="{{ route('locale.update') }}" data-no-loading>
                    @csrf
                    <input type="hidden" name="locale" value="{{ $locale }}">
                    <button type="submit" class="locale-inline__item">{{ __('ui.locale_'.$locale) }}</button>
                </form>
            @endif
        @endforeach
    </div>
@else
    {{-- Menu variant: plain buttons that drive the shell's parked form, so the
         account menu never owns the page's first submit button. See
         partials/shell-forms. --}}
    <li><h6 class="dropdown-header">{{ __('ui.change_language') }}</h6></li>

    @foreach ($locales as $locale)
        <li>
            <button type="button"
                    class="dropdown-item"
                    data-shell-submit="#ramsLocaleForm"
                    data-shell-field="locale"
                    data-shell-value="{{ $locale }}"
                    @if ($locale === $current) aria-current="true" @endif>
                @if ($locale === $current)
                    <i class="bi bi-check2 text-brand" aria-hidden="true"></i>
                @else
                    <i class="bi bi-translate" aria-hidden="true"></i>
                @endif
                {{ __('ui.locale_'.$locale) }}
            </button>

            @if ($locale !== $current)
                <noscript>
                    <form method="POST" action="{{ route('locale.update') }}">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $locale }}">
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-translate" aria-hidden="true"></i>
                            {{ __('ui.locale_'.$locale) }}
                        </button>
                    </form>
                </noscript>
            @endif
        </li>
    @endforeach
@endif
