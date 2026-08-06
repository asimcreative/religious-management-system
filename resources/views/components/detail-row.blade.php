{{--
    One label/value pair inside <x-detail-list>. Falls back to an em dash so
    detail pages never show a blank gap where data is missing.
--}}
@props(['label', 'value' => null])

<div class="detail-list__row">
    <dt class="detail-list__label">{{ $label }}</dt>
    <dd class="detail-list__value mb-0">
        @if (trim($slot) !== '')
            {{ $slot }}
        @elseif (filled($value))
            {{ $value }}
        @else
            <span class="dash" aria-label="{{ __('ui.not_provided') }}">—</span>
        @endif
    </dd>
</div>
