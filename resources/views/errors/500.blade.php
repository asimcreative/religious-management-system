<x-error-page code="500" :title="__('ui.error_500_title')" :message="__('ui.error_500_text')" tone="danger" glyph="⚠">
    <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}" class="btn btn-primary">
        {{ __('ui.error_back_home') }}
    </a>
</x-error-page>
