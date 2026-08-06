{{--
    Password field with a reveal toggle and an optional strength meter.
    The toggle is a real button so it is reachable by keyboard and announced
    with aria-pressed. Falls back to a normal password field without JS.
--}}
@props([
    'name' => 'password',
    'label' => null,
    'required' => true,
    'help' => null,
    'autocomplete' => 'current-password',
    'meter' => false,
    'id' => null,
    'autofocus' => false,
])

@php
    $id = $id ?? $name;
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
    $describedBy = array_filter([
        $help ? "{$id}-help" : null,
        $meter ? "{$id}-strength" : null,
        $hasError ? "{$id}-error" : null,
    ]);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="req" aria-hidden="true">*</span>
                <span class="visually-hidden">({{ __('ui.required') }})</span>
            @endif
        </label>
    @endif

    <div class="password-field">
        <input
            type="password"
            name="{{ $name }}"
            id="{{ $id }}"
            autocomplete="{{ $autocomplete }}"
            @if ($required) required aria-required="true" @endif
            @if ($autofocus) autofocus @endif
            @if ($meter) data-password-meter @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
            {{ $attributes->merge(['class' => 'form-control'.($hasError ? ' is-invalid' : '')]) }}
        >

        <button type="button"
                class="password-field__toggle"
                data-password-toggle="{{ $id }}"
                aria-pressed="false"
                aria-controls="{{ $id }}"
                aria-label="{{ __('ui.show_password') }}"
                title="{{ __('ui.show_password') }}"
                data-label-show="{{ __('ui.show_password') }}"
                data-label-hide="{{ __('ui.hide_password') }}">
            <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
    </div>

    @if ($meter)
        <div class="pw-meter"
             data-meter-for="{{ $id }}"
             data-score="0"
             data-labels="{{ __('ui.pw_weak') }}|{{ __('ui.pw_fair') }}|{{ __('ui.pw_good') }}|{{ __('ui.pw_strong') }}"
             aria-hidden="true">
            <span></span><span></span><span></span><span></span>
        </div>
        <p class="pw-hint" id="{{ $id }}-strength" data-meter-hint="{{ $id }}" aria-live="polite"></p>
    @endif

    @if ($help)
        <div class="form-text" id="{{ $id }}-help">{{ $help }}</div>
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-flex" id="{{ $id }}-error">{{ $message }}</div>
    @enderror
</div>
