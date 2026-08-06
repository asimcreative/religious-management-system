{{--
    Empty state — icon, friendly explanation and the next best action.
    Every list, table and panel must show one instead of a blank area.
--}}
@props([
    'icon' => 'bi-inbox',
    'title' => null,
    'text' => null,
    'size' => null, // 'sm'
])

<div {{ $attributes->merge(['class' => 'empty-state'.($size === 'sm' ? ' empty-state--sm' : '')]) }}>
    <span class="empty-state__art" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>

    @if ($title)
        <p class="empty-state__title">{{ $title }}</p>
    @endif

    @if ($text)
        <p class="empty-state__text">{{ $text }}</p>
    @endif

    @if (trim($slot) !== '')
        <div class="d-flex flex-wrap gap-2 justify-content-center no-print">{{ $slot }}</div>
    @endif
</div>
