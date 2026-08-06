{{--
    Result summary + pagination. Tells the user exactly where they are in the
    dataset ("Showing 26–50 of 312") instead of bare page numbers.
--}}
@props(['paginator'])

@if ($paginator->total() > 0)
    <div class="table-footer">
        <p class="table-footer__count mb-0" aria-live="polite">
            {{ __('ui.showing_results', [
                'first' => number_format($paginator->firstItem() ?? 0),
                'last'  => number_format($paginator->lastItem() ?? 0),
                'total' => number_format($paginator->total()),
            ]) }}
        </p>

        {{ $paginator->withQueryString()->links() }}
    </div>
@endif
