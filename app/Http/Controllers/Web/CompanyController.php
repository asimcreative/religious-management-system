<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\CompanyProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Tenant administration.
 *
 * Every action is gated by CompanyPolicy, which requires the platform account
 * — a Company Admin with company.* permissions manages their own company from
 * Settings, not from here.
 */
class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly CompanyProvisioningService $provisioning,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Company::class);

        return view('companies.index', [
            'companies' => $this->companies->search(
                $request->query('search'),
                $request->query('status'),
                $this->perPage($request),
            ),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('companies.create', ['statuses' => Status::cases()]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        // A tenant without its baseline master data is not usable — the attendance
        // sheets can only record "present" until it has attendance reasons — so the
        // company and its defaults are created together or not at all.
        DB::transaction(function () use ($request): void {
            /** @var Company $company */
            $company = $this->companies->create($request->validated());

            $this->provisioning->provision($company);
        });

        return redirect()
            ->route('companies.index')
            ->with('success', __('companies.created', ['name' => $request->validated('company_name')]));
    }

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        return view('companies.edit', [
            'company' => $company,
            'statuses' => Status::cases(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->companies->update($company, $request->validated());

        return redirect()
            ->route('companies.index')
            ->with('success', __('companies.updated', ['name' => $company->company_name]));
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $this->companies->delete($company);

        return redirect()
            ->route('companies.index')
            ->with('success', __('companies.deleted'));
    }
}
