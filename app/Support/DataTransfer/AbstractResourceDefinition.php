<?php

namespace App\Support\DataTransfer;

use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Shared behaviour for every module definition.
 *
 * Subclasses declare what is genuinely module-specific — the model, the
 * columns, the permission names, the filters — and inherit the rest. In
 * practice a module definition is about forty lines.
 */
abstract class AbstractResourceDefinition implements ResourceDefinitionContract
{
    /**
     * Attributes that are never accepted from a spreadsheet, whatever the
     * file contains.
     *
     * company_id is the important one: it is the tenant boundary and is always
     * taken from the authenticated user, never from the uploaded data. The
     * timestamps and audit columns are excluded so an import cannot forge
     * authorship or backdate a record.
     *
     * @var array<int, string>
     */
    protected const GUARDED_KEYS = [
        'id',
        'company_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /** @var array<int, Column>|null */
    private ?array $memoizedColumns = null;

    /**
     * Declare the module's fields. Called once per definition instance.
     *
     * @return array<int, Column>
     */
    abstract protected function defineColumns(): array;

    // ── Columns ────────────────────────────────────────────────────

    /** @return array<int, Column> */
    public function columns(): array
    {
        return $this->memoizedColumns ??= array_values(array_filter(
            $this->defineColumns(),
            fn (Column $column): bool => ! in_array($column->key, static::GUARDED_KEYS, true),
        ));
    }

    /** @return array<int, Column> */
    public function exportColumns(): array
    {
        return array_values(array_filter(
            $this->columns(),
            static fn (Column $column): bool => $column->isExportable(),
        ));
    }

    /** @return array<int, Column> */
    public function importColumns(): array
    {
        return array_values(array_filter(
            $this->columns(),
            static fn (Column $column): bool => $column->isImportable(),
        ));
    }

    public function column(string $key): ?Column
    {
        foreach ($this->columns() as $column) {
            if ($column->key === $key) {
                return $column;
            }
        }

        return null;
    }

    // ── Identity ───────────────────────────────────────────────────

    public function singularLabel(): string
    {
        return Str::singular($this->label());
    }

    public function icon(): string
    {
        return 'bi-table';
    }

    public function indexRoute(): ?string
    {
        return null;
    }

    /**
     * Base name for generated files, e.g. "employees" in employees_2026-08-06.xlsx.
     */
    public function fileBaseName(): string
    {
        return Str::of($this->key())->replace('-', '_')->snake()->toString();
    }

    // ── Capability ─────────────────────────────────────────────────

    public function supportsImport(): bool
    {
        return true;
    }

    public function supportsExport(): bool
    {
        return true;
    }

    public function permissionFor(string $ability): ?string
    {
        return $this->permissions()[$ability] ?? null;
    }

    /** @return array<int, string> */
    public function uniqueBy(): array
    {
        return array_values(array_map(
            static fn (Column $column): string => $column->key,
            array_filter(
                $this->importColumns(),
                static fn (Column $column): bool => $column->isUnique(),
            ),
        ));
    }

    /** @return array<int, array<int, string>> */
    public function uniqueGroups(): array
    {
        return [];
    }

    /**
     * The column a bulk status change would write to.
     *
     * Derived rather than declared, because every module already describes its
     * status as an enum column — employees happen to call theirs
     * "employment_status". Returning null opts a module out of the action.
     */
    public function statusColumn(): ?Column
    {
        foreach ($this->columns() as $column) {
            if (! $column->isEnum() || ! $column->isImportable()) {
                continue;
            }

            if ($column->key === 'status' || str_ends_with($column->key, '_status')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Whether this module offers row selection and bulk actions.
     *
     * Modules whose rows are events rather than records opt out: attendance
     * and notifications are things that happened, and offering to delete
     * forty of them at once is offering to lose history, not to save time.
     */
    public function supportsBulkActions(): bool
    {
        return true;
    }

    /**
     * Whether one record may be deleted, beyond what the policy decides.
     *
     * Modules with referential rules — an employee with attendance history —
     * override this so a bulk delete refuses exactly what the single-record
     * delete would refuse.
     */
    public function canDelete(Model $record): bool
    {
        return true;
    }

    // ── Query ──────────────────────────────────────────────────────

    public function newQuery(): Builder
    {
        $model = $this->modelClass();

        return $model::query()->with($this->eagerLoads());
    }

    /**
     * Relations derived from the lookup columns, plus anything a computed
     * column needs. Declaring lookups therefore eager-loads them for free.
     *
     * @return array<int, string>
     */
    public function eagerLoads(): array
    {
        $relations = [];

        foreach ($this->exportColumns() as $column) {
            $relation = $column->getRelation();

            if ($relation !== null) {
                $relations[] = $relation;
            }
        }

        return array_values(array_unique(array_merge($relations, $this->extraEagerLoads())));
    }

    /**
     * Relations a computed column reads that no lookup column implies.
     *
     * @return array<int, string>
     */
    protected function extraEagerLoads(): array
    {
        return [];
    }

    /**
     * Columns the free-text "search" filter matches with LIKE.
     *
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return [];
    }

    /**
     * Related columns the free-text search should also match.
     *
     * Transactional modules carry no readable text of their own — an
     * attendance row is a date and a set of ids — so a search that only looked
     * at the table itself would never find anything a person typed.
     *
     * @return array<string, array<int, string>> Relation => its columns.
     */
    protected function searchRelations(): array
    {
        return [];
    }

    /**
     * Filter key => column name, or a closure for anything more involved.
     *
     * @return array<string, string|Closure>
     */
    protected function filters(): array
    {
        return [];
    }

    /** @return array<int, string> */
    public function filterKeys(): array
    {
        $searchable = $this->searchColumns() !== [] || $this->searchRelations() !== [];

        return array_values(array_merge(
            $searchable ? ['search'] : [],
            array_keys($this->filters()),
        ));
    }

    /** @param  array<string, mixed>  $filters */
    public function applyFilters(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;

        if (is_string($search) && $search !== '') {
            $this->applySearch($query, $search);
        }

        foreach ($this->filters() as $key => $target) {
            $value = $filters[$key] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if ($target instanceof Closure) {
                $target($query, $value);

                continue;
            }

            $query->where($target, $value);
        }
    }

    /**
     * One free-text term matched across this module's own columns and any
     * related columns it borrows, all inside a single grouped WHERE so the
     * OR clauses cannot leak past the filters applied around them.
     */
    private function applySearch(Builder $query, string $search): void
    {
        $columns = $this->searchColumns();
        $relations = $this->searchRelations();

        if ($columns === [] && $relations === []) {
            return;
        }

        $query->where(function (Builder $sub) use ($search, $columns, $relations): void {
            foreach ($columns as $column) {
                $sub->orWhere($column, 'like', '%'.$search.'%');
            }

            foreach ($relations as $relation => $relationColumns) {
                $sub->orWhereHas($relation, function (Builder $related) use ($search, $relationColumns): void {
                    $related->where(function (Builder $inner) use ($search, $relationColumns): void {
                        foreach ($relationColumns as $column) {
                            $inner->orWhere($column, 'like', '%'.$search.'%');
                        }
                    });
                });
            }
        });
    }

    /**
     * Exports are ordered so that two runs of the same export produce the
     * same file — an unordered query would shuffle rows between runs and
     * make diffing exports impossible.
     */
    public function applySorting(Builder $query): void
    {
        foreach ($this->defaultSort() as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return [];
    }

    // ── Row hooks ──────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function validateRow(array $attributes, array $context): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function prepareForWrite(array $attributes, array $context): array
    {
        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $context
     */
    public function afterWrite(Model $record, array $attributes, array $context): void
    {
        //
    }
}
