<?php

namespace App\Support\Analytics;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Works out which tables a particular question needs, and joins each one once.
 *
 * A report screen combines one or two breakdowns with any number of filters,
 * and each of those independently declares the joins it needs. Grouping by
 * Department while filtering by Designation must not join `employees` twice,
 * and asking for Branch must not require the caller to remember that Branch is
 * only reachable through `employees`.
 *
 * So: collect the names, walk their prerequisites, sort so a dependency is
 * always applied before its dependant, and apply. Nothing is joined that the
 * current question does not actually use — a plain "group by prayer" report
 * touches two tables, not eleven.
 */
final class JoinPlan
{
    /** @var array<string, Join> */
    private array $available;

    /** @var array<string, true> */
    private array $needed = [];

    /** @param array<string, Join> $available */
    public function __construct(array $available)
    {
        $this->available = $available;
    }

    /** @param list<string> $names */
    public function need(array $names): void
    {
        foreach ($names as $name) {
            $this->add($name);
        }
    }

    public function applyTo(Builder $query): void
    {
        foreach ($this->ordered() as $join) {
            $query->leftJoin($join->target(), $join->on);
        }
    }

    private function add(string $name): void
    {
        if (isset($this->needed[$name])) {
            return;
        }

        if (! isset($this->available[$name])) {
            // A definition asked for a join it never declared. That is a bug in
            // the definition, not bad user input, so it should be loud.
            throw new InvalidArgumentException("Analytics join [{$name}] is not declared.");
        }

        // Marked before recursing so a cycle in the declarations cannot spin.
        $this->needed[$name] = true;

        foreach ($this->available[$name]->requires as $prerequisite) {
            $this->add($prerequisite);
        }
    }

    /**
     * Dependencies first, then the joins that build on them.
     *
     * @return list<Join>
     */
    private function ordered(): array
    {
        $ordered = [];
        $placed = [];

        $place = function (string $name) use (&$place, &$ordered, &$placed): void {
            if (isset($placed[$name])) {
                return;
            }

            $placed[$name] = true;

            foreach ($this->available[$name]->requires as $prerequisite) {
                $place($prerequisite);
            }

            $ordered[] = $this->available[$name];
        };

        foreach (array_keys($this->needed) as $name) {
            $place($name);
        }

        return $ordered;
    }
}
