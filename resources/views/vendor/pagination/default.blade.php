{{-- Framework default view name — aliased to the RAMS design-system pagination. --}}
@include('pagination::rams', ['paginator' => $paginator, 'elements' => $elements])
