<x-error-page code="404" :title="__('ui.error_404_title')" :message="__('ui.error_404_text')" tone="info" glyph="?">
    <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}" class="btn btn-primary">
        {{ __('ui.error_back_home') }}
    </a>
</x-error-page>
