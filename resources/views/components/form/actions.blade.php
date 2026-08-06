{{--
    Sticky footer for long forms — the save action stays reachable without
    scrolling to the bottom of the page.
--}}
@props([
    'submit' => null,
    'cancel' => null,
    'cancelUrl' => null,
    'hint' => null,
    'icon' => 'bi-check-lg',
])

<div {{ $attributes->merge(['class' => 'form-actions']) }}>
    @if ($hint)
        <span class="form-actions__hint">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>{{ $hint }}
        </span>
    @else
        <span class="form-actions__hint">
            <span class="req" aria-hidden="true">*</span> {{ __('ui.required_fields_note') }}
        </span>
    @endif

    @if ($cancelUrl)
        <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">
            {{ $cancel ?? __('ui.cancel') }}
        </a>
    @endif

    <button type="submit" class="btn btn-primary">
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
        <span>{{ $submit ?? __('ui.save') }}</span>
    </button>

    {{ $slot }}
</div>
