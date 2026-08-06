{{--
    Quick jump (Ctrl/⌘ + K).

    Indexes the links already rendered in the sidebar, which are permission
    filtered server-side — so it can never surface a page the user may not
    open. Purely client-side: no new route, no new query.
--}}
<div class="modal fade quicknav" id="ramsQuickNav" tabindex="-1" aria-labelledby="ramsQuickNavLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <h2 class="visually-hidden" id="ramsQuickNavLabel">{{ __('ui.quick_jump') }}</h2>

            <div class="quicknav__search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       data-quicknav-input
                       placeholder="{{ __('ui.quick_jump_placeholder') }}"
                       aria-label="{{ __('ui.quick_jump_placeholder') }}"
                       autocomplete="off"
                       spellcheck="false">
                <button type="button" class="btn btn-sm btn-ghost" data-bs-dismiss="modal">
                    {{ __('ui.esc') }}
                </button>
            </div>

            <div class="quicknav__results" data-quicknav-results role="listbox" aria-label="{{ __('ui.pages') }}"></div>

            <p class="quicknav__empty" data-quicknav-empty hidden>{{ __('ui.no_matching_pages') }}</p>

            <div class="quicknav__foot">
                <span><kbd>↑</kbd> <kbd>↓</kbd> {{ __('ui.navigate') }}</span>
                <span><kbd>↵</kbd> {{ __('ui.open') }}</span>
            </div>
        </div>
    </div>
</div>
