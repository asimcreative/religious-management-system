{{--
    Validation error summary (WCAG 3.3.1 / 3.3.3).

    After a failed submit the user lands at the top of the page with no idea
    what went wrong. This block states how many fields failed, links straight
    to each one and is announced by screen readers.
--}}
@props(['title' => null])

@if ($errors->any())
    <div class="alert alert-danger" role="alert" tabindex="-1" data-error-summary>
        <i class="bi bi-exclamation-octagon-fill alert__icon" aria-hidden="true"></i>
        <div class="alert__body">
            <strong class="alert__title">
                {{ $title ?? trans_choice('ui.validation_summary', $errors->count(), ['count' => $errors->count()]) }}
            </strong>

            <ul class="mb-0 ps-3">
                @foreach ($errors->keys() as $field)
                    <li>
                        <a href="#{{ str_replace('.', '_', $field) }}" class="text-reset">
                            {{ $errors->first($field) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
