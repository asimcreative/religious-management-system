{{--
    Surface card. `flush` removes body padding (for tables and lists).

    Usage:
        <x-card :title="__('reports.filters')" icon="bi-funnel">
            <x-slot:actions>…</x-slot:actions>
            body
        </x-card>
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'flush' => false,
    'actions' => null,
    'footer' => null,
    'bodyClass' => '',
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || $actions)
        <div class="card-header">
            @if ($title)
                <h2 class="card-title">
                    @if ($icon)
                        <i class="bi {{ $icon }} text-soft" aria-hidden="true"></i>
                    @endif
                    <span>{{ $title }}</span>
                    @if ($subtitle)
                        <span class="card-subtitle">{{ $subtitle }}</span>
                    @endif
                </h2>
            @endif

            @if ($actions)
                <div class="card-header__actions">{{ $actions }}</div>
            @endif
        </div>
    @endif

    @if ($flush)
        {{ $slot }}
    @else
        <div class="card-body {{ $bodyClass }}">{{ $slot }}</div>
    @endif

    @if ($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endif
</div>
