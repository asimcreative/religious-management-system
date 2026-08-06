{{--
    Page header — the single title/action block used at the top of every page.

    Usage:
        <x-page-header :title="__('employees.employees')"
                       :subtitle="__('employees.subtitle')"
                       icon="bi-people">
            <x-slot:actions> ...buttons... </x-slot:actions>
        </x-page-header>
--}}
@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'badge' => null,
    'badgeTone' => 'neutral',
    'actions' => null,
])

<header {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="page-header__text">
        <h1 class="page-header__title">
            @if ($icon)
                <i class="bi {{ $icon }} text-brand" aria-hidden="true"></i>
            @endif
            <span class="min-w-0">{{ $title }}</span>
            @if ($badge)
                <span class="badge-soft badge-soft-{{ $badgeTone }}">{{ $badge }}</span>
            @endif
        </h1>

        @if ($subtitle)
            <p class="page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($actions)
        <div class="page-header__actions no-print">
            {{ $actions }}
        </div>
    @endif
</header>
