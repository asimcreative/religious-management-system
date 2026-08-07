{{--
    Shown on every page while the platform account is signed in to a company.

    Two things must never be in doubt: whose data is on screen, and how to get
    back out. So the banner names the company, states plainly that nothing can
    be changed, and carries the way back — it is not tucked into a menu, because
    someone who has forgotten they are impersonating will not go looking there.

    The bar sits directly under the topbar and scrolls away with the page. It is
    excluded from print: a printed page belongs to the company it is about.
--}}
@if (App\Support\Impersonation::isActive())
    @php($company = App\Support\Impersonation::companyName())

    <div class="rams-impersonation no-print" role="status">
        <span class="rams-impersonation__icon" aria-hidden="true">
            <i class="bi bi-eye"></i>
        </span>

        <span class="rams-impersonation__text">
            <strong>{{ __('companies.impersonation_viewing', ['company' => $company]) }}</strong>
            <span>
                {{ __('companies.impersonation_as', ['name' => auth()->user()?->name]) }}
                @if (App\Support\Impersonation::isReadOnly())
                    — {{ __('companies.impersonation_read_only_note') }}
                @endif
            </span>
        </span>

        <form method="POST" action="{{ route('impersonate.stop') }}" class="rams-impersonation__exit">
            @csrf
            <button type="submit" class="btn btn-sm btn-light">
                <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                <span>{{ __('companies.impersonation_stop') }}</span>
            </button>
        </form>
    </div>
@endif
