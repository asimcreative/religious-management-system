@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 3,
    'required' => false,
    'help' => null,
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

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
        {{ $attributes->merge(['class' => 'form-control'.($hasError ? ' is-invalid' : '')]) }}
    >{{ old($errorKey, $value) }}</textarea>

    @if ($help)
        <div class="form-text" id="{{ $id }}-help">{{ $help }}</div>
    @endif

    @error($errorKey)
        <div class="invalid-feedback d-flex" id="{{ $id }}-error">{{ $message }}</div>
    @enderror
</div>
