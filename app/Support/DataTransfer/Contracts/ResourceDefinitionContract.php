<?php

namespace App\Support\DataTransfer\Contracts;

use App\Support\DataTransfer\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Everything the transfer engine needs to know about one module.
 *
 * A module implements this once. Export in three formats, import with
 * validation and preview, the sample workbook and the toolbar are all derived
 * from it — there is no per-module import class, export class or controller.
 */
interface ResourceDefinitionContract
{
    /** URL-safe identifier, e.g. "employees". Used in every transfer route. */
    public function key(): string;

    /** @return class-string<Model> */
    public function modelClass(): string;

    /** Translated plural name, shown in the UI and in log entries. */
    public function label(): string;

    /** Translated singular name, used in buttons and confirmation copy. */
    public function singularLabel(): string;

    /** Bootstrap Icons class representing the module. */
    public function icon(): string;

    /**
     * The fields carried by exports, imports and sample sheets.
     *
     * @return array<int, Column>
     */
    public function columns(): array;

    /** @return array<int, Column> */
    public function exportColumns(): array;

    /** @return array<int, Column> */
    public function importColumns(): array;

    public function column(string $key): ?Column;

    /** Base name for generated files, e.g. "employees". */
    public function fileBaseName(): string;

    /**
     * Permission strings keyed by ability: view, import, export, sample.
     *
     * Each module keeps its own naming — some use one "*.manage" right,
     * others separate "*.view" / "*.import" rights. The engine only asks.
     *
     * @return array<string, string>
     */
    public function permissions(): array;

    /** The permission required for an ability, or null when unrestricted. */
    public function permissionFor(string $ability): ?string;

    public function supportsImport(): bool;

    public function supportsExport(): bool;

    /**
     * Columns forming the natural key used for duplicate detection and for
     * matching an existing record when the user chooses "update existing".
     *
     * @return array<int, string>
     */
    public function uniqueBy(): array;

    /**
     * Natural keys made of more than one column.
     *
     * Membership tables have no single unique column — a class member is
     * identified by the class *and* the employee together. Each entry is the
     * set of column keys that jointly identify a record.
     *
     * @return array<int, array<int, string>>
     */
    public function uniqueGroups(): array;

    /** The enum column a bulk status change writes to, or null to opt out. */
    public function statusColumn(): ?Column;

    /** Whether this module offers row selection and bulk actions. */
    public function supportsBulkActions(): bool;

    /** Whether one record may be deleted, beyond what the policy decides. */
    public function canDelete(Model $record): bool;

    /**
     * Run after a row has been written, inside the same transaction.
     *
     * The hook exists for relationships a spreadsheet cell cannot express on
     * its own — the branches a teacher covers, for instance, which live in a
     * pivot table and can only be attached once the record has an id.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     */
    public function afterWrite(Model $record, array $attributes, array $context): void;

    /**
     * Query parameters that narrow this module's list, so a filtered export
     * reproduces exactly what the user is looking at.
     *
     * @return array<int, string>
     */
    public function filterKeys(): array;

    /** A tenant-scoped, eager-loaded query for this module's records. */
    public function newQuery(): Builder;

    /** @param  array<string, mixed>  $filters */
    public function applyFilters(Builder $query, array $filters): void;

    public function applySorting(Builder $query): void;

    /**
     * Relations an export must eager-load. preventLazyLoading() is enabled
     * outside production, so an omission here fails loudly rather than
     * silently issuing one query per row.
     *
     * @return array<int, string>
     */
    public function eagerLoads(): array;

    /**
     * Module-specific rules a row must satisfy beyond its column validation —
     * attendance lock windows, cross-field consistency, capacity limits.
     *
     * @param  array<string, mixed>  $attributes  Cast, lookup-resolved values.
     * @param  array<string, mixed>  $context  company_id, row_number, user.
     * @return array<int, string> Human-readable errors; empty means valid.
     */
    public function validateRow(array $attributes, array $context): array;

    /**
     * Last chance to shape an accepted row before it is written.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareForWrite(array $attributes, array $context): array;

    /** Route name of the module's list page, for links back from history. */
    public function indexRoute(): ?string;
}
