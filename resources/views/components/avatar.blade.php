{{--
    Initials avatar. Purely decorative — the name is always adjacent in the
    DOM, so it is hidden from assistive technology.
--}}
@props([
    'name' => '',
    'size' => null, // null | 'lg' | 'xl'
    'src' => null,
])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<span {{ $attributes->merge(['class' => 'rams-avatar'.($size ? ' rams-avatar--'.$size : '')]) }} aria-hidden="true">
    @if ($src)
        <img src="{{ $src }}" alt="" loading="lazy" decoding="async"
             style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
    @else
        {{ $initials !== '' ? $initials : '—' }}
    @endif
</span>
