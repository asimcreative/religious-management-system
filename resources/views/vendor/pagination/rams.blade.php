{{--
    RAMS pagination.

    The framework default (`pagination::tailwind`) emits Tailwind utility
    classes, which this Bootstrap-only build never compiles — pagination
    therefore rendered unstyled. This view provides design-system markup and is
    aliased from `pagination::tailwind` and `pagination::default`, so no
    controller or service has to change.
--}}
@if ($paginator->hasPages())
    <nav aria-label="{{ __('ui.pagination') }}">
        <ul class="pagination pagination-sm mb-0">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">{{ __('ui.previous') }}</span></span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">{{ __('ui.previous') }}</span>
                    </a>
                </li>
            @endif

            {{-- Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}" aria-label="{{ __('ui.go_to_page', ['page' => $page]) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">{{ __('ui.next') }}</span>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link"><i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">{{ __('ui.next') }}</span></span>
                </li>
            @endif

        </ul>
    </nav>
@endif
