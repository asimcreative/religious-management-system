<x-error-page code="403" :title="__('ui.error_403_title')" :message="__('ui.error_403_text')" tone="warning" glyph="⛔">
    <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}" class="btn btn-primary">
        {{ __('ui.error_back_home') }}
    </a>
</x-error-page>
