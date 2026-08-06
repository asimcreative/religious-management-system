{{--
    Dashboard KPI tile. Renders as a link when `href` is supplied so the
    number itself becomes the shortcut to the underlying list.
--}}
@props([
    'label',
    'value',
    'icon' => 'bi-graph-up',
    'tone' => 'primary',
    'href' => null,
    'hint' => null,
    'meta' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'stat-card tone-'.$tone]) }}
>
    <div class="stat-card__head">
        <div class="min-w-0">
            <div class="stat-card__label">{{ $label }}</div>
            <div class="stat-card__value">{{ $value }}</div>
        </div>
        <span class="stat-card__icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    </div>

    @if ($meta)
        <div class="stat-card__meta">{{ $meta }}</div>
    @endif

    @if ($hint)
        <div class="stat-card__foot">
            {{ $hint }}
            @if ($href)
                <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            @endif
        </div>
    @endif
</{{ $tag }}>
