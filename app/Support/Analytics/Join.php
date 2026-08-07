<?php

namespace App\Support\Analytics;

use Illuminate\Database\Query\JoinClause;

/**
 * One table a breakdown or filter needs, and how to reach it.
 *
 * Dimensions and filters declare the joins they depend on by name; JoinPlan
 * resolves the names, pulls in whatever those joins themselves depend on, and
 * applies each one exactly once. So "Department" and "Designation" can both
 * say they need `employees` without either having to know the other exists.
 *
 * Every join is a LEFT JOIN. An employee with no department still attended,
 * and dropping their record would quietly change the totals — instead they
 * group under "Not set", which is usually the more useful answer anyway.
 */
final class Join
{
    /**
     * @param  string  $name  How dimensions and filters refer to this join.
     * @param  string  $table  Real table name.
     * @param  string|null  $alias  Alias, when the same table is joined twice
     *                              (employees appear as both the attendee and
     *                              the jamaat leader).
     * @param  list<string>  $requires  Joins that must be applied first.
     * @param  \Closure  $on  The join condition, as fn (JoinClause $join) => …
     */
    private function __construct(
        public readonly string $name,
        public readonly string $table,
        public readonly ?string $alias,
        public readonly array $requires,
        public readonly \Closure $on,
    ) {}

    /**
     * @param  list<string>  $requires
     * @param  \Closure  $on  fn (JoinClause $join) => …
     */
    public static function make(string $name, string $table, \Closure $on, array $requires = [], ?string $alias = null): self
    {
        return new self($name, $table, $alias, $requires, $on);
    }

    /** What to write in SQL: `table as alias`, or just `table`. */
    public function target(): string
    {
        return $this->alias === null ? $this->table : $this->table.' as '.$this->alias;
    }

    /** The prefix columns of this join are addressed by. */
    public function prefix(): string
    {
        return $this->alias ?? $this->table;
    }
}
