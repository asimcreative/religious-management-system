{{--
    Shell-owned POST forms, parked at the end of <body>.

    The account menu sits above the page content in the DOM, so any submit
    button it renders becomes the *first* submit button on every screen. That is
    a real hazard: anything that targets "the page's submit button" — automation,
    a keyboard macro, an assistive shortcut — would hit Logout instead of the
    form the user is actually filling in.

    So the shell contributes **no** submit buttons. Its menu items are plain
    buttons that ask ui.js to submit one of these forms, and each carries a
    <noscript> fallback so the action still works with JavaScript disabled.
    The page's own form always owns the first submit button.
--}}
<form method="POST" action="{{ route('logout') }}" id="ramsLogoutForm" class="d-none" data-no-loading>
    @csrf
</form>

<form method="POST" action="{{ route('locale.update') }}" id="ramsLocaleForm" class="d-none" data-no-loading>
    @csrf
    <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
</form>
