{{--
    Letterhead that only appears in printed / PDF output.

    Screen users already have the page header and filter chips; a printed
    report needs the organisation name, report title, applied filters and a
    generation timestamp so the paper copy stands on its own.
--}}
@props([
    'title',
    'subtitle' => null,
    'filters' => [],
])

<div class="print-header print-only" aria-hidden="true">
    <div class="print-header__brand">
        <div>
            <p class="print-header__title">{{ $title }}</p>
            @if ($subtitle)
                <p class="print-header__sub">{{ $subtitle }}</p>
            @endif
        </div>
        <div>
            <div class="print-header__org">{{ config('app.name', 'RAMS') }}</div>
            <div class="print-header__meta">
                {{ __('ui.generated_on', ['datetime' => now()->format('d M Y, H:i')]) }}<br>
                {{ __('ui.generated_by', ['name' => auth()->user()?->name ?? '—']) }}
            </div>
        </div>
    </div>

    @if (! empty(array_filter($filters)))
        <div class="print-header__filters">
            @foreach (array_filter($filters) as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    @endif
</div>
