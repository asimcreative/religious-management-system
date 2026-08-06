{{--
    Status pill.

    Accepts either an App\Enums\Status case (uses its own label) or a plain
    boolean/label pair, so every "Active / Inactive / Suspended" indicator in
    the app looks and reads identically.
--}}
@props([
    'status' => null,
    'label' => null,
    'tone' => null,
])

@php
    $resolvedTone = $tone;
    $resolvedLabel = $label;

    if ($status instanceof \App\Enums\Status) {
        $resolvedLabel ??= $status->label();
        $resolvedTone ??= match ($status) {
            \App\Enums\Status::Active => 'success',
            \App\Enums\Status::Suspended => 'danger',
            \App\Enums\Status::Inactive => 'neutral',
        };
    }

    if (is_bool($status)) {
        $resolvedTone ??= $status ? 'success' : 'neutral';
        $resolvedLabel ??= $status ? __('ui.active') : __('ui.inactive');
    }

    $resolvedTone ??= 'neutral';
@endphp

<span {{ $attributes->merge(['class' => 'badge-soft badge-soft-'.$resolvedTone]) }}>{{ $resolvedLabel }}</span>
