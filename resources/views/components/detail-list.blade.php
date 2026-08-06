{{-- Semantic definition list used on every detail / show page. --}}
<dl {{ $attributes->merge(['class' => 'detail-list']) }}>
    {{ $slot }}
</dl>
