{{--
    Text-style input with label, required marker, help text, icon and
    validation feedback. Renders a plain native <input>, so browser
    validation, autofill and assistive technology behave normally.
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'icon' => null,
    'placeholder' => null,
    'id' => null,
    'optional' => false,
])

@php
    $id = $id ?? $name;
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
    $describedBy = array_filter([
        $help ? "{$id}-help" : null,
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
            @elseif ($optional)
                <span class="opt">{{ __('ui.optional') }}</span>
            @endif
        </label>
    @endif

    <div @class(['input-icon' => $icon])>
        @if ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ old($errorKey, $value) }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
            {{ $attributes->merge(['class' => 'form-control'.($hasError ? ' is-invalid' : '')]) }}
        >
    </div>

    @if ($help)
        <div class="form-text" id="{{ $id }}-help">{{ $help }}</div>
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-flex" id="{{ $id }}-error">{{ $message }}</div>
    @enderror
</div>
