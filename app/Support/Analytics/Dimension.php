<?php

namespace App\Support\Analytics;

use Closure;

/**
 * One way of cutting the numbers up — "by department", "by prayer", "by month".
 *
 * A dimension is three expressions and the joins that make them valid: what to
 * group on, what to show as the row label, and what to sort by. Keeping the
 * group and the label separate matters — two departments may share a name, and
 * grouping on the name would silently merge them into one row.
 *
 * `labelUsing` turns a stored value into something a reader recognises without
 * pushing a CASE expression into SQL: 1 becomes Active, `male` becomes its
 * translation, 3 becomes Tuesday. It also catches the empty group, which is
 * often the most interesting row on the page — "Not set" against a large count
 * means somebody's records are missing a department.
 */
final class Dimension
{
    /**
     * @param  list<string>  $joins  Join names this dimension needs.
     * @param  string  $groupBy  SQL expression that identifies the group.
     * @param  string  $labelExpression  SQL expression for the row label.
     * @param  string|null  $orderBy  SQL expression to sort by; null sorts by
     *                                the primary measure, biggest first.
     * @param  string|null  $drillFilter  Filter key that narrows the detail
     *                                    report to one row of this breakdown.
     * @param  (Closure(int|string|null, ?string): string)|null  $labelUsing
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $joins,
        public readonly string $groupBy,
        public readonly string $labelExpression,
        public readonly ?string $orderBy,
        public readonly ?string $drillFilter,
        public readonly ?string $group,
        public readonly ?Closure $labelUsing,
    ) {}

    /**
     * @param  list<string>  $joins
     * @param  (Closure(int|string|null, ?string): string)|null  $labelUsing
     */
    public static function make(
        string $key,
        string $label,
        string $groupBy,
        string $labelExpression,
        array $joins = [],
        ?string $orderBy = null,
        ?string $drillFilter = null,
        ?string $group = null,
        ?Closure $labelUsing = null,
    ): self {
        return new self($key, $label, $joins, $groupBy, $labelExpression, $orderBy, $drillFilter, $group, $labelUsing);
    }

    /**
     * A dimension whose label and grouping are the same column — dates, enums,
     * and anything else that is its own identity.
     *
     * @param  list<string>  $joins
     * @param  (Closure(int|string|null, ?string): string)|null  $labelUsing
     */
    public static function raw(
        string $key,
        string $label,
        string $expression,
        array $joins = [],
        ?string $orderBy = null,
        ?string $drillFilter = null,
        ?string $group = null,
        ?Closure $labelUsing = null,
    ): self {
        return new self($key, $label, $joins, $expression, $expression, $orderBy ?? $expression, $drillFilter, $group, $labelUsing);
    }

    /**
     * A dimension that groups on a foreign key and labels from the master table.
     *
     * @param  list<string>  $joins
     * @param  (Closure(int|string|null, ?string): string)|null  $labelUsing
     */
    public static function lookup(
        string $key,
        string $label,
        string $keyColumn,
        string $labelColumn,
        array $joins = [],
        ?string $orderBy = null,
        ?string $drillFilter = null,
        ?string $group = null,
        ?Closure $labelUsing = null,
    ): self {
        return new self($key, $label, $joins, $keyColumn, $labelColumn, $orderBy ?? $labelColumn, $drillFilter, $group, $labelUsing);
    }

    /**
     * What the reader sees for one group.
     *
     * Nothing that reaches here is trusted to be non-empty: a LEFT JOIN that
     * found no master row, an employee with no department, an attendance record
     * with no reason recorded all arrive as null and must still name themselves.
     */
    public function displayLabel(int|string|null $key, ?string $rawLabel): string
    {
        if ($this->labelUsing !== null) {
            return ($this->labelUsing)($key, $rawLabel);
        }

        return $rawLabel === null || $rawLabel === '' ? __('analytics.not_set') : $rawLabel;
    }
}
