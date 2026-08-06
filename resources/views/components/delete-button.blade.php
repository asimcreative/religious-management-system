{{--
    Destructive action button.

    - Still a real POST form with @method('DELETE') — the route, verb, CSRF
      token and controller are untouched.
    - With JS: an accessible confirmation modal naming the record.
    - Without JS: the inline window.confirm() fallback still guards the
      submit (ui.js removes it once the modal is available, so the user is
      never asked twice).
--}}
@props([
    'action',
    'confirm' => null,
    'title' => null,
    'label' => null,
    'record' => null,
    'icon' => 'bi-trash',
    'variant' => 'icon', // 'icon' | 'button' | 'menu'
    'method' => 'DELETE',
    'tone' => 'danger',
])

@php
    $message = $confirm ?? ($record
        ? __('ui.confirm_delete_record', ['name' => $record])
        : __('ui.confirm_delete'));

    $buttonLabel = $label ?? __('ui.delete');
    $tooltip = $title ?? $buttonLabel;
@endphp

<form method="POST"
      action="{{ $action }}"
      class="d-inline"
      data-no-loading
      data-confirm-fallback
      onsubmit="return confirm('{{ addslashes($message) }}')">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <button type="submit"
            @class([
                'btn btn-sm btn-ghost-danger btn-icon' => $variant === 'icon' && $tone === 'danger',
                'btn btn-sm btn-ghost btn-icon' => $variant === 'icon' && $tone !== 'danger',
                'btn btn-'.$tone => $variant === 'button',
                'dropdown-item is-danger' => $variant === 'menu',
            ])
            data-confirm="{{ $message }}"
            data-confirm-title="{{ $title ?? __('ui.confirm_action') }}"
            data-confirm-accept="{{ $buttonLabel }}"
            data-confirm-cancel="{{ __('ui.cancel') }}"
            data-confirm-tone="{{ $tone }}"
            data-confirm-icon="{{ $icon }}"
            @if ($variant === 'icon')
                data-bs-toggle="tooltip" title="{{ $tooltip }}" aria-label="{{ $tooltip }}"
            @endif>
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @if ($variant !== 'icon')
            <span>{{ $buttonLabel }}</span>
        @endif
    </button>
</form>
