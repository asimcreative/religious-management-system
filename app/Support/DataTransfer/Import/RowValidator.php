<?php

namespace App\Support\DataTransfer\Import;

use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Validator;

/**
 * Decides what happens to each row of an uploaded file.
 *
 * Works a chunk at a time so that uniqueness can be checked with one query per
 * column per chunk instead of one query per row, and so that peak memory stays
 * proportional to the chunk rather than to the file.
 *
 * Nothing here writes. A row's fate is decided in full before the importer is
 * allowed to touch the database, which is what lets the user see the whole
 * picture on the preview screen and still change their mind.
 */
class RowValidator
{
    /** @var array<string, array<string, int>> Unique column key => value => first row that used it. */
    private array $seenInFile = [];

    public function __construct(
        private readonly ValueCaster $caster,
        private readonly LookupResolver $lookups,
    ) {}

    /**
     * Load the lookup tables and reset the cross-chunk duplicate memory.
     */
    public function prepare(ResourceDefinitionContract $definition): void
    {
        $this->seenInFile = [];
        $this->lookups->preload($definition->importColumns());
    }

    /**
     * @param  array<int, array{row: int, values: array<int, mixed>}>  $chunk
     * @param  array<string, mixed>  $context
     * @return array<int, RowOutcome>
     */
    public function validateChunk(
        ResourceDefinitionContract $definition,
        HeaderMap $map,
        array $chunk,
        array $context,
    ): array {
        $columns = $this->mappedColumns($definition, $map);
        $candidates = [];

        foreach ($chunk as $entry) {
            $candidates[] = $this->castRow($definition, $map, $columns, $entry);
        }

        $existing = $this->findExisting($definition, $columns, $candidates);
        $existingGroups = $this->findExistingGroups($definition, $candidates);
        $outcomes = [];

        foreach ($candidates as $candidate) {
            $outcomes[] = $this->judge($definition, $columns, $candidate, $existing, $existingGroups, $context);
        }

        return $outcomes;
    }

    /**
     * Resolve multi-column natural keys for this chunk.
     *
     * A membership row is identified by the pair (class, employee), which no
     * single-column check can see. One query per group per chunk narrows on
     * the first column, then the tuples are matched in PHP — cheaper and far
     * more portable than asking the database to compare concatenations.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<int, array<string, array{id: int, trashed: bool}>>
     */
    private function findExistingGroups(ResourceDefinitionContract $definition, array $candidates): array
    {
        $groups = $definition->uniqueGroups();

        if ($groups === []) {
            return [];
        }

        $modelClass = $definition->modelClass();
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
        $found = [];

        foreach ($groups as $index => $group) {
            $leadColumn = $group[0];
            $leadValues = [];

            foreach ($candidates as $candidate) {
                $value = $candidate['attributes'][$leadColumn] ?? null;

                if ($value !== null && $value !== '') {
                    $leadValues[(string) $value] = $value;
                }
            }

            if ($leadValues === []) {
                continue;
            }

            /** @var Builder $query */
            $query = $modelClass::query();

            if ($usesSoftDeletes) {
                // withTrashed() is a SoftDeletes macro the Builder does not
                // declare; it is defined as exactly this scope removal.
                $query->withoutGlobalScope(SoftDeletingScope::class);
            }

            $map = [];

            $query->whereIn($leadColumn, array_values($leadValues))
                ->get(array_merge(['id'], $group, $usesSoftDeletes ? ['deleted_at'] : []))
                ->each(function ($record) use (&$map, $group): void {
                    $key = $this->groupKey($group, fn (string $column) => $record->getAttribute($column));

                    $map[$key] = [
                        'id' => (int) $record->getKey(),
                        'trashed' => $record->getAttribute('deleted_at') !== null,
                    ];
                });

            $found[$index] = $map;
        }

        return $found;
    }

    /**
     * @param  array<int, string>  $group
     * @param  callable(string): mixed  $value
     */
    private function groupKey(array $group, callable $value): string
    {
        return implode("\x1F", array_map(
            static fn (string $column): string => mb_strtolower((string) $value($column)),
            $group,
        ));
    }

    /**
     * Cast every cell of one row, without yet deciding whether the row is good.
     *
     * @param  array<int, Column>  $columns
     * @param  array{row: int, values: array<int, mixed>}  $entry
     * @return array<string, mixed>
     */
    private function castRow(
        ResourceDefinitionContract $definition,
        HeaderMap $map,
        array $columns,
        array $entry,
    ): array {
        $row = $entry['row'];
        $values = $entry['values'];

        if ($this->isBlank($values)) {
            return ['row' => $row, 'blank' => true, 'raw' => $values];
        }

        if ($this->isUntouchedExampleRow($map, $columns, $values, $row)) {
            return ['row' => $row, 'example' => true, 'raw' => $values];
        }

        $attributes = [];
        $errors = [];
        $rejected = [];

        foreach ($columns as $column) {
            $result = $this->caster->cast($column, $map->valueFor($column->key, $values));

            if (! $result->isValid()) {
                $errors[] = new RowError($row, $column->getLabel(), (string) $result->error, $result->fix);
                $rejected[$column->key] = true;

                continue;
            }

            $attributes[$column->key] = $result->value;
        }

        $this->resolveLookups($columns, $attributes, $errors, $row, $rejected);

        return [
            'row' => $row,
            'raw' => $values,
            'attributes' => $attributes,
            'errors' => $errors,
            'rejected' => $rejected,
        ];
    }

    /**
     * Replace every lookup name with the id it refers to inside this company.
     *
     * @param  array<int, Column>  $columns
     * @param  array<string, mixed>  $attributes
     * @param  array<int, RowError>  $errors
     * @param  array<string, true>  $rejected
     */
    private function resolveLookups(array $columns, array &$attributes, array &$errors, int $row, array &$rejected): void
    {
        foreach ($columns as $column) {
            if (! $column->isLookup() || ! array_key_exists($column->key, $attributes)) {
                continue;
            }

            $name = $attributes[$column->key];

            if ($name === null || $name === '') {
                $attributes[$column->key] = null;

                continue;
            }

            if ($this->lookups->isAmbiguous($column, (string) $name)) {
                $errors[] = new RowError(
                    $row,
                    $column->getLabel(),
                    __('data_transfer.errors.lookup_ambiguous', ['column' => $column->getLabel(), 'value' => $name]),
                    __('data_transfer.fixes.generic'),
                );
                unset($attributes[$column->key]);
                $rejected[$column->key] = true;

                continue;
            }

            $id = $this->lookups->resolve($column, (string) $name);

            if ($id === null) {
                $errors[] = new RowError(
                    $row,
                    $column->getLabel(),
                    __('data_transfer.errors.lookup_missing', ['column' => $column->getLabel(), 'value' => $name]),
                    __('data_transfer.fixes.lookup_missing', ['value' => $name]),
                );
                unset($attributes[$column->key]);
                $rejected[$column->key] = true;

                continue;
            }

            $attributes[$column->key] = $id;
        }
    }

    /**
     * Look up every unique value in this chunk with one query per column.
     *
     * Soft-deleted records are included on purpose. They still occupy the
     * database's unique index, so ignoring them would turn a clear "already
     * exists" message into an integrity-constraint crash mid-import.
     *
     * @param  array<int, Column>  $columns
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<string, array<string, array{id: int, trashed: bool}>>
     */
    private function findExisting(ResourceDefinitionContract $definition, array $columns, array $candidates): array
    {
        $modelClass = $definition->modelClass();
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
        $existing = [];

        foreach ($columns as $column) {
            if (! $column->isUnique()) {
                continue;
            }

            $wanted = [];

            foreach ($candidates as $candidate) {
                $value = $candidate['attributes'][$column->key] ?? null;

                if ($value !== null && $value !== '') {
                    $wanted[(string) $column->uniqueValue($value)] = true;
                }
            }

            if ($wanted === []) {
                continue;
            }

            $storedColumn = $column->uniqueColumn();

            /** @var Builder $query */
            $query = $modelClass::query();

            if ($usesSoftDeletes) {
                // withTrashed() is a SoftDeletes macro the Builder does not
                // declare; it is defined as exactly this scope removal.
                $query->withoutGlobalScope(SoftDeletingScope::class);
            }

            $found = [];

            $query->whereIn($storedColumn, array_keys($wanted))
                ->get(['id', $storedColumn, ...($usesSoftDeletes ? ['deleted_at'] : [])])
                ->each(function ($record) use (&$found, $storedColumn): void {
                    $found[(string) $record->getAttribute($storedColumn)] = [
                        'id' => (int) $record->getKey(),
                        'trashed' => $record->getAttribute('deleted_at') !== null,
                    ];
                });

            $existing[$column->key] = $found;
        }

        return $existing;
    }

    /**
     * @param  array<int, Column>  $columns
     * @param  array<string, mixed>  $candidate
     * @param  array<string, array<string, array{id: int, trashed: bool}>>  $existing
     * @param  array<int, array<string, array{id: int, trashed: bool}>>  $existingGroups
     * @param  array<string, mixed>  $context
     */
    private function judge(
        ResourceDefinitionContract $definition,
        array $columns,
        array $candidate,
        array $existing,
        array $existingGroups,
        array $context,
    ): RowOutcome {
        $row = $candidate['row'];

        if ($candidate['blank'] ?? false) {
            return RowOutcome::blank($row);
        }

        if ($candidate['example'] ?? false) {
            return RowOutcome::example($row, $candidate['raw']);
        }

        $attributes = $candidate['attributes'];
        $errors = $candidate['errors'];

        $errors = array_merge($errors, $this->applyColumnRules($columns, $attributes, $row, $candidate['rejected'] ?? []));
        $errors = array_merge($errors, $this->checkFileDuplicates($columns, $attributes, $row));

        $duplicateOf = null;

        foreach ($columns as $column) {
            if (! $column->isUnique()) {
                continue;
            }

            $value = $attributes[$column->key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $match = $existing[$column->key][(string) $column->uniqueValue($value)] ?? null;

            if ($match === null) {
                continue;
            }

            if ($match['trashed']) {
                $errors[] = new RowError(
                    $row,
                    $column->getLabel(),
                    __('data_transfer.errors.duplicate_in_database', ['column' => $column->getLabel(), 'value' => $value]),
                    __('data_transfer.fixes.duplicate_in_database', ['column' => $column->getLabel()]),
                );

                continue;
            }

            $duplicateOf ??= $match['id'];
        }

        foreach ($definition->uniqueGroups() as $index => $group) {
            $complete = true;

            foreach ($group as $columnKey) {
                if (($attributes[$columnKey] ?? null) === null) {
                    $complete = false;

                    break;
                }
            }

            if (! $complete) {
                continue;
            }

            $key = $this->groupKey($group, static fn (string $column) => $attributes[$column]);
            $match = $existingGroups[$index][$key] ?? null;

            if ($match === null) {
                if (isset($this->seenInFile['__group_'.$index][$key])) {
                    $errors[] = new RowError(
                        $row,
                        null,
                        __('data_transfer.errors.duplicate_in_file', [
                            'column' => $definition->singularLabel(),
                            'value' => str_replace("\x1F", ' + ', $key),
                        ]),
                        __('data_transfer.fixes.duplicate_in_file'),
                    );

                    continue;
                }

                $this->seenInFile['__group_'.$index][$key] = $row;

                continue;
            }

            if ($match['trashed']) {
                $errors[] = new RowError(
                    $row,
                    null,
                    __('data_transfer.errors.duplicate_in_database', [
                        'column' => $definition->singularLabel(),
                        'value' => str_replace("\x1F", ' + ', $key),
                    ]),
                    __('data_transfer.fixes.duplicate_in_database', ['column' => $definition->singularLabel()]),
                );

                continue;
            }

            $duplicateOf ??= $match['id'];
        }

        if ($errors === []) {
            $errors = array_map(
                static fn (string $message): RowError => new RowError($row, null, $message, __('data_transfer.fixes.generic')),
                $definition->validateRow($attributes, $context + ['row' => $row]),
            );
        }

        if ($errors !== []) {
            return RowOutcome::invalid($row, $candidate['raw'], $errors);
        }

        return $duplicateOf === null
            ? RowOutcome::valid($row, $attributes, $candidate['raw'])
            : RowOutcome::duplicate($row, $attributes, $candidate['raw'], $duplicateOf);
    }

    /**
     * Validate the cast values.
     *
     * Columns whose cell could not be cast are left out: they already have a
     * precise error ("Status must be one of: Active, Inactive"), and running
     * the rules over the now-absent value would add a vaguer second complaint
     * about the same cell.
     *
     * @param  array<int, Column>  $columns
     * @param  array<string, mixed>  $attributes
     * @param  array<string, true>  $rejected
     * @return array<int, RowError>
     */
    private function applyColumnRules(array $columns, array $attributes, int $row, array $rejected): array
    {
        $rules = [];
        $names = [];

        foreach ($columns as $column) {
            if (isset($rejected[$column->key])) {
                continue;
            }

            $rules[$column->key] = $column->validationRules();
            $names[$column->key] = $column->getLabel();
        }

        $validator = Validator::make($attributes, $rules, [], $names);

        if ($validator->passes()) {
            return [];
        }

        $errors = [];

        foreach ($validator->errors()->toArray() as $key => $messages) {
            foreach ($messages as $message) {
                $errors[] = new RowError($row, $names[$key] ?? $key, $message, __('data_transfer.fixes.generic'));
            }
        }

        return $errors;
    }

    /**
     * Catch the same unique value used twice inside one file, which the
     * database would only complain about halfway through the import.
     *
     * @param  array<int, Column>  $columns
     * @param  array<string, mixed>  $attributes
     * @return array<int, RowError>
     */
    private function checkFileDuplicates(array $columns, array $attributes, int $row): array
    {
        $errors = [];

        foreach ($columns as $column) {
            if (! $column->isUnique()) {
                continue;
            }

            $value = $attributes[$column->key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $key = mb_strtolower((string) $value);

            if (isset($this->seenInFile[$column->key][$key])) {
                $errors[] = new RowError(
                    $row,
                    $column->getLabel(),
                    __('data_transfer.errors.duplicate_in_file', ['column' => $column->getLabel(), 'value' => $value]),
                    __('data_transfer.fixes.duplicate_in_file'),
                );

                continue;
            }

            $this->seenInFile[$column->key][$key] = $row;
        }

        return $errors;
    }

    /**
     * The importable columns the file actually provides.
     *
     * @return array<int, Column>
     */
    private function mappedColumns(ResourceDefinitionContract $definition, HeaderMap $map): array
    {
        return array_values(array_filter(
            $definition->importColumns(),
            static fn (Column $column): bool => $map->has($column->key),
        ));
    }

    /** @param  array<int, mixed>  $values */
    private function isBlank(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Recognise the demonstration row from the sample sheet left in place.
     *
     * Two conditions, both deliberately strict, because wrongly discarding
     * someone's data would be far worse than importing a stray example:
     *
     *   - it must be the first data row, which is where the template puts its
     *     example and where a forgotten one therefore stays;
     *   - every mapped column with an example value must match it exactly.
     *
     * A row further down the file that happens to resemble the example is
     * imported normally.
     *
     * @param  array<int, Column>  $columns
     * @param  array<int, mixed>  $values
     */
    private function isUntouchedExampleRow(HeaderMap $map, array $columns, array $values, int $row): bool
    {
        if ($row !== 2) {
            return false;
        }

        $compared = 0;

        foreach ($columns as $column) {
            $sample = $column->sampleValue();

            if ($sample === null || $sample === '') {
                continue;
            }

            $cell = $map->valueFor($column->key, $values);

            if (trim((string) $cell) !== trim((string) $sample)) {
                return false;
            }

            $compared++;
        }

        return $compared > 1;
    }
}
