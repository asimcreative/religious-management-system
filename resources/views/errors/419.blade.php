<x-error-page code="419" :title="__('ui.error_419_title')" :message="__('ui.error_419_text')" tone="warning" glyph="⏱">
    <a href="{{ \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/') }}" class="btn btn-primary">
        {{ __('auth.login') }}
    </a>
</x-error-page>
