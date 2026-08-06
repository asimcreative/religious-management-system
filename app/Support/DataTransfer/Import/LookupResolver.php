<?php

namespace App\Support\DataTransfer\Import;

use App\Support\DataTransfer\Column;

/**
 * Turns the names written in a sheet into the foreign keys the model stores.
 *
 * This class is the reason imports cannot cross a tenant boundary. Every map
 * is built from the model, so only the importing company's records are ever
 * in it — a row naming another company's branch resolves to nothing and is
 * reported as "not found", which is exactly what it is from where that user
 * is standing.
 *
 * Each lookup table is read once per import rather than once per row. On a
 * fifty-thousand-row file that is the difference between a handful of queries
 * and fifty thousand.
 */
class LookupResolver
{
    /** Marks a name that more than one record answers to. */
    private const AMBIGUOUS = -1;

    /** @var array<string, array<string, int>> Column key => normalized name => id. */
    private array $maps = [];

    /**
     * Load every lookup table this import will consult.
     *
     * @param  array<int, Column>  $columns
     */
    public function preload(array $columns): void
    {
        foreach ($columns as $column) {
            if (! $column->isLookup() || isset($this->maps[$column->key])) {
                continue;
            }

            $this->maps[$column->key] = $this->buildMap($column);
        }
    }

    /**
     * @return int|null The id, or null when nothing matches.
     */
    public function resolve(Column $column, string $name): ?int
    {
        $id = $this->maps[$column->key][$this->normalize($name)] ?? null;

        return $id === self::AMBIGUOUS ? null : $id;
    }

    /** Whether the name matches more than one record, which is a different fault. */
    public function isAmbiguous(Column $column, string $name): bool
    {
        return ($this->maps[$column->key][$this->normalize($name)] ?? null) === self::AMBIGUOUS;
    }

    /** @return array<string, int> */
    private function buildMap(Column $column): array
    {
        $modelClass = $column->getLookupModel();
        $lookupColumn = $column->getLookupColumn();

        if ($modelClass === null || $lookupColumn === null) {
            return [];
        }

        $map = [];

        $modelClass::query()
            ->select(['id', $lookupColumn])
            ->orderBy('id')
            ->chunk(1000, function ($records) use (&$map, $lookupColumn): void {
                foreach ($records as $record) {
                    $name = $record->getAttribute($lookupColumn);

                    if (! is_string($name) || $name === '') {
                        continue;
                    }

                    $key = $this->normalize($name);

                    // Two records answering to one name make every reference to
                    // it a guess, so refuse to guess.
                    $map[$key] = isset($map[$key]) ? self::AMBIGUOUS : (int) $record->getKey();
                }
            });

        return $map;
    }

    /**
     * Match on meaning rather than presentation: trailing spaces and casing
     * differences from a hand-edited sheet should not fail a lookup.
     */
    private function normalize(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)) ?? $name);
    }
}
