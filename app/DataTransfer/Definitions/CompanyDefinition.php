<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Company;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

/**
 * Export only, and only for the platform account.
 *
 * A company is a tenant boundary, not a business record. Creating one from a
 * spreadsheet would create data partitions nobody reviewed, so this module
 * exports the tenant register and nothing more.
 */
class CompanyDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'companies';
    }

    public function modelClass(): string
    {
        return Company::class;
    }

    public function label(): string
    {
        return __('companies.companies');
    }

    public function singularLabel(): string
    {
        return __('companies.company');
    }

    public function icon(): string
    {
        return 'bi-buildings';
    }

    public function indexRoute(): string
    {
        return 'companies.index';
    }

    public function supportsImport(): bool
    {
        return false;
    }

    public function supportsBulkActions(): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'company.view',
            'export' => 'company.view',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::string('company_code')->label(__('companies.company_code'))->width(16),
            Column::string('company_name')->label(__('companies.company_name'))->width(28),
            Column::email('email')->label(__('companies.email'))->width(24),
            Column::phone('phone')->label(__('companies.phone')),
            Column::string('city')->label(__('companies.city')),
            Column::string('country')->label(__('companies.country')),
            Column::string('timezone')->label(__('companies.timezone'))->width(20),
            Column::string('subscription_plan')->label(__('companies.subscription_plan')),
            Column::date('subscription_expiry')->label(__('companies.subscription_expiry')),
            Column::enum('status', Status::class)->label(__('companies.status')),
        ];
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['company_name', 'company_code', 'email'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return ['status' => 'status'];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}
