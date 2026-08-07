<?php

namespace App\Support\Analytics;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The datasets a report can be run against, by key.
 *
 * Registered from config rather than discovered, so adding a dataset is a
 * deliberate line in a file that can be read and reviewed, and an unknown key
 * in a URL is a 404 rather than an attempt to instantiate whatever was typed.
 */
class AnalyticsRegistry
{
    /** @var array<string, AbstractAnalyticsDefinition> */
    private array $resolved = [];

    /**
     * @param  array<string, class-string<AbstractAnalyticsDefinition>>  $definitions
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $definitions,
    ) {}

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    public function get(string $key): AbstractAnalyticsDefinition
    {
        if (! $this->has($key)) {
            throw new NotFoundHttpException("Unknown analytics dataset [{$key}].");
        }

        return $this->resolved[$key] ??= $this->container->make($this->definitions[$key]);
    }

    /**
     * Every dataset the given check allows, in declaration order.
     *
     * @param  callable(AbstractAnalyticsDefinition): bool  $allowed
     * @return list<AbstractAnalyticsDefinition>
     */
    public function visible(callable $allowed): array
    {
        $visible = [];

        foreach (array_keys($this->definitions) as $key) {
            $definition = $this->get($key);

            if ($allowed($definition)) {
                $visible[] = $definition;
            }
        }

        return $visible;
    }
}
