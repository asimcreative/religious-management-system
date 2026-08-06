{{--
    Responsive table shell.

    - horizontal scroll is contained here, never on <body>
    - `sticky` keeps the header visible while scrolling long result sets
    - `stack` turns rows into labelled cards below the sm breakpoint
      (each <td> needs data-label="…")
--}}
@props([
    'sticky' => false,
    'stack' => true,
    'label' => null,
])

<div @class(['table-wrap', 'table-wrap--sticky' => $sticky])>
    <table {{ $attributes->merge(['class' => 'table align-middle'.($stack ? ' table-stack' : '')]) }}
        @if ($label) aria-label="{{ $label }}" @endif>
        {{ $slot }}
    </table>
</div>
