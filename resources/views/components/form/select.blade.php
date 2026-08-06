{{--
    Native <select> with label and validation feedback.

    Native is deliberate: it is keyboard accessible, screen-reader friendly,
    works on every mobile OS and needs no JavaScript.

    Pass either an `options` array (value => label) or raw <option> markup
    through the default slot.
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'help' => null,
    'id' => null,
    'optional' => false,
])

@php
    $id = $id ?? $name;
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
    $current = old($errorKey, $selected);
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

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
        {{ $attributes->merge(['class' => 'form-select'.($hasError ? ' is-invalid' : '')]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach

        {{ $slot }}
    </select>

    @if ($help)
        <div class="form-text" id="{{ $id }}-help">{{ $help }}</div>
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-flex" id="{{ $id }}-error">{{ $message }}</div>
    @enderror
</div>
