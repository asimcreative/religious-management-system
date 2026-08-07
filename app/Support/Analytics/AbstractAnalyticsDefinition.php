<?php

namespace App\Support\Analytics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Everything one dataset can be asked.
 *
 * A definition declares its joins, the ways it can be broken down, the ways it
 * can be narrowed, and the numbers it reports. AnalyticsService then answers
 * any combination of those without knowing what dataset it is looking at, and
 * one screen renders any definition without knowing either.
 *
 * That is the reason this exists rather than twenty-five hand-written reports:
 * "attendance by department", "by designation", "by branch", "by jamaat", "by
 * qari" are not five reports, they are one report and five dimensions. Adding
 * a sixth means adding a row to dimensions(), not a controller, a query, a
 * view, an export and a test.
 */
abstract class AbstractAnalyticsDefinition
{
    /** @var array<string, Join>|null */
    private ?array $joinCache = null;

    /** @var array<string, Dimension>|null */
    private ?array $dimensionCache = null;

    /** @var array<string, Filter>|null */
    private ?array $filterCache = null;

    /** Stable key used in URLs and permissions. */
    abstract public function key(): string;

    abstract public function label(): string;

    /** One line saying what this dataset is, shown under the page title. */
    abstract public function description(): string;

    /** Permission required to run any report on this dataset. */
    abstract public function permission(): string;

    /**
     * The base query, already tenant-scoped by the model's global scopes.
     */
    abstract public function query(): Builder;

    /** Table the base query selects from, for qualifying column names. */
    abstract public function table(): string;

    /** @return list<Join> */
    abstract protected function joinList(): array;

    /** @return list<Dimension> */
    abstract protected function dimensionList(): array;

    /** @return list<Filter> */
    abstract protected function filterList(): array;

    /** @return list<Measure> */
    abstract public function measures(): array;

    /**
     * Route and filter key that opens the underlying records for one group.
     *
     * @return array{route: string, filters: array<string, string>}|null
     */
    public function drillDown(): ?array
    {
        return null;
    }

    /** The breakdown offered before the reader chooses one. */
    public function defaultDimension(): string
    {
        return array_key_first($this->dimensions()) ?? '';
    }

    /** @return array<string, Join> */
    final public function joins(): array
    {
        if ($this->joinCache === null) {
            $this->joinCache = [];

            foreach ($this->joinList() as $join) {
                $this->joinCache[$join->name] = $join;
            }
        }

        return $this->joinCache;
    }

    /** @return array<string, Dimension> */
    final public function dimensions(): array
    {
        if ($this->dimensionCache === null) {
            $this->dimensionCache = [];

            foreach ($this->dimensionList() as $dimension) {
                $this->dimensionCache[$dimension->key] = $dimension;
            }
        }

        return $this->dimensionCache;
    }

    /** @return array<string, Filter> */
    final public function filters(): array
    {
        if ($this->filterCache === null) {
            $this->filterCache = [];

            foreach ($this->filterList() as $filter) {
                $this->filterCache[$filter->key] = $filter;
            }
        }

        return $this->filterCache;
    }

    final public function dimension(?string $key): Dimension
    {
        $dimensions = $this->dimensions();

        // An unknown or missing key falls back rather than failing: a stale
        // bookmark should still show a report, not a 500.
        return $dimensions[$key] ?? $dimensions[$this->defaultDimension()] ?? reset($dimensions);
    }

    final public function hasDimension(?string $key): bool
    {
        return $key !== null && $key !== '' && isset($this->dimensions()[$key]);
    }

    /**
     * Dimensions arranged under their headings, for an optgroup'd selector.
     *
     * @return array<string, array<string, string>>
     */
    final public function groupedDimensions(): array
    {
        $grouped = [];

        foreach ($this->dimensions() as $dimension) {
            $grouped[$dimension->group ?? ''][$dimension->key] = $dimension->label;
        }

        return $grouped;
    }

    /**
     * Filters arranged under their headings, so the filter bar can put the
     * everyday ones first and fold the rest away.
     *
     * @return array<string, list<Filter>>
     */
    final public function groupedFilters(): array
    {
        $grouped = [];

        foreach ($this->filters() as $filter) {
            $grouped[$filter->group ?? ''][] = $filter;
        }

        return $grouped;
    }

    /** @return list<string> */
    final public function filterKeys(): array
    {
        return array_keys($this->filters());
    }
}
