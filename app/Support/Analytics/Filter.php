<?php

namespace App\Support\Analytics;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * One way of narrowing the numbers down.
 *
 * A filter knows four things: how to draw itself, where its choices come from,
 * which joins it needs before its condition is valid, and how to apply that
 * condition. Declaring all four together is what lets the screen render every
 * filter a dataset offers without the view knowing anything about any of them.
 *
 * Options are a closure rather than an array because they are master data —
 * reading them on construction would query the database for filters the user
 * never opens, on every request.
 */
final class Filter
{
    /**
     * @param  list<string>  $joins
     * @param  (Closure(): array<int|string, string>)|null  $options
     * @param  Closure  $apply  fn (Builder $query, string $value) => …
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly FilterType $type,
        public readonly array $joins,
        public readonly ?Closure $options,
        public readonly Closure $apply,
        public readonly ?string $placeholder,
        public readonly ?string $group,
        public readonly string $width,
    ) {}

    /**
     * A dropdown of master data.
     *
     * @param  Closure(): array<int|string, string>  $options
     * @param  Closure  $apply  fn (Builder $query, string $value) => …
     * @param  list<string>  $joins
     */
    public static function select(
        string $key,
        string $label,
        Closure $options,
        Closure $apply,
        array $joins = [],
        ?string $group = null,
        string $width = 'field--md',
    ): self {
        return new self($key, $label, FilterType::Select, $joins, $options, $apply, null, $group, $width);
    }

    /**
     * @param  Closure  $apply  fn (Builder $query, string $value) => …
     * @param  list<string>  $joins
     */
    public static function date(
        string $key,
        string $label,
        Closure $apply,
        array $joins = [],
        ?string $group = null,
    ): self {
        return new self($key, $label, FilterType::Date, $joins, null, $apply, null, $group, 'field--sm');
    }

    /**
     * @param  Closure  $apply  fn (Builder $query, string $value) => …
     * @param  list<string>  $joins
     */
    public static function text(
        string $key,
        string $label,
        Closure $apply,
        array $joins = [],
        ?string $placeholder = null,
        ?string $group = null,
        string $width = 'field--grow',
    ): self {
        return new self($key, $label, FilterType::Text, $joins, null, $apply, $placeholder, $group, $width);
    }

    /**
     * @param  Closure  $apply  fn (Builder $query, string $value) => …
     * @param  list<string>  $joins
     */
    public static function number(
        string $key,
        string $label,
        Closure $apply,
        array $joins = [],
        ?string $placeholder = null,
        ?string $group = null,
    ): self {
        return new self($key, $label, FilterType::Number, $joins, null, $apply, $placeholder, $group, 'field--xs');
    }

    /** @return array<int|string, string> */
    public function options(): array
    {
        return $this->options === null ? [] : ($this->options)();
    }

    /**
     * How this filter's current value reads on screen and in an export, so the
     * reader can see what the numbers were narrowed to without re-deriving it
     * from the URL.
     */
    public function describe(string $value): string
    {
        if (! $this->type->isSelect()) {
            return $value;
        }

        return $this->options()[$value] ?? $value;
    }
}
