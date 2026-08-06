{{-- Grouped block of fields inside a form card, with a heading and hint. --}}
@props([
    'title' => null,
    'hint' => null,
    'icon' => null,
])

<section {{ $attributes->merge(['class' => 'form-section']) }}>
    @if ($title)
        <div class="form-section__head">
            <h2 class="form-section__title">
                @if ($icon)
                    <i class="bi {{ $icon }} text-soft" aria-hidden="true"></i>
                @endif
                {{ $title }}
            </h2>
            @if ($hint)
                <p class="form-section__hint">{{ $hint }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
