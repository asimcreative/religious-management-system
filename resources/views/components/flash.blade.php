{{--
    Flash messages.

    Rendered server-side as real, dismissible alerts so they work with
    JavaScript disabled. ui.js upgrades success/info messages into toasts
    (transient, non-blocking) and leaves warnings and errors on the page,
    because those usually need the user to do something.
--}}
@php
    $messages = [
        'success' => session('success'),
        'error'   => session('error'),
        'warning' => session('warning'),
        'info'    => session('info'),
        'status'  => session('status'),
    ];

    $meta = [
        'success' => ['tone' => 'success', 'icon' => 'bi-check-circle-fill',        'toast' => true],
        'status'  => ['tone' => 'success', 'icon' => 'bi-check-circle-fill',        'toast' => true],
        'info'    => ['tone' => 'info',    'icon' => 'bi-info-circle-fill',         'toast' => true],
        'warning' => ['tone' => 'warning', 'icon' => 'bi-exclamation-triangle-fill','toast' => false],
        'error'   => ['tone' => 'danger',  'icon' => 'bi-exclamation-octagon-fill', 'toast' => false],
    ];
@endphp

@foreach ($messages as $key => $message)
    @continue(blank($message))

    @php($config = $meta[$key])

    <div class="alert alert-{{ $config['tone'] }} alert-dismissible fade show no-print"
         role="{{ in_array($key, ['error', 'warning'], true) ? 'alert' : 'status' }}"
         aria-live="{{ $key === 'error' ? 'assertive' : 'polite' }}"
         @if ($config['toast'])
             data-flash="{{ $key === 'status' ? 'success' : $key }}"
             data-flash-message="{{ $message }}"
         @endif>
        <i class="bi {{ $config['icon'] }} alert__icon" aria-hidden="true"></i>
        <div class="alert__body">{{ $message }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.dismiss') }}"></button>
    </div>
@endforeach
