{{--
    "Sign in to this company" — the platform account's way into a tenant.

    A real POST form so the action is never reachable by following a link, and
    gated by CompanyPolicy::impersonate, which excludes the platform's own
    company. The resulting session is read-only; the confirmation says so,
    because arriving inside someone else's company unannounced is disorienting.

    Usage:
        <x-impersonate-button :company="$company" />
        <x-impersonate-button :company="$company" variant="button" />
--}}
@props([
    'company',
    'variant' => 'icon', // 'icon' | 'button'
])

@can('impersonate', $company)
    @php($label = __('companies.impersonate'))

    <form method="POST" action="{{ route('impersonate.start', $company) }}" class="d-inline" data-no-loading>
        @csrf

        <button type="submit"
                @class([
                    'btn btn-sm btn-ghost btn-icon' => $variant === 'icon',
                    'btn btn-sm btn-outline-secondary' => $variant === 'button',
                ])
                data-confirm="{{ __('companies.impersonate_confirm', ['company' => $company->company_name]) }}"
                data-confirm-title="{{ $label }}"
                data-confirm-accept="{{ $label }}"
                data-confirm-cancel="{{ __('ui.cancel') }}"
                {{-- Not destructive, so not the red modal — but not routine
                     either, which is what the amber one is for. --}}
                data-confirm-tone="warning"
                data-confirm-icon="bi-box-arrow-in-right"
                @if ($variant === 'icon')
                    data-bs-toggle="tooltip"
                    title="{{ $label }}"
                    aria-label="{{ $label }} — {{ $company->company_name }}"
                @endif>
            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
            @if ($variant !== 'icon')
                <span>{{ $label }}</span>
            @endif
        </button>
    </form>
@endcan
