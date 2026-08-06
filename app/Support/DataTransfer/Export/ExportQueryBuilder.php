<?php

namespace App\Support\DataTransfer\Export;

use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\ExportOptions;
use App\Support\DataTransfer\ExportScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns an export request into the query that produces its rows.
 *
 * Tenancy and role boundaries are not applied here and must not be: the query
 * starts from the model, so the BelongsToCompany and RestrictsRoleDataAccess
 * global scopes are already on it. This class only ever *narrows* that query.
 * Nothing in the transfer engine may call withoutGlobalScopes().
 */
class ExportQueryBuilder
{
    public function build(ResourceDefinitionContract $definition, ExportOptions $options): Builder
    {
        $query = $definition->newQuery();

        if ($options->scope->honoursFilters()) {
            $definition->applyFilters($query, $options->filters);
        }

        $definition->applySorting($query);

        if ($options->scope === ExportScope::Selected) {
            // Applied on top of the scoped query, so ids belonging to another
            // company simply match nothing rather than leaking a row.
            $query->whereKey($options->ids);
        }

        if ($options->scope === ExportScope::Page) {
            $query->whereKey($this->keysOnPage($query, $options));
        }

        return $query;
    }

    /**
     * Resolve the primary keys shown on one page of the list.
     *
     * The page is turned into an explicit key set rather than left as a
     * limit/offset because the spreadsheet writer re-chunks whatever builder
     * it is handed, and chunking a builder that already carries its own limit
     * silently returns the wrong rows.
     *
     * @return array<int, mixed>
     */
    private function keysOnPage(Builder $query, ExportOptions $options): array
    {
        return $query->clone()
            ->forPage($options->page, $options->perPage)
            ->pluck($query->getModel()->getKeyName())
            ->all();
    }
}
