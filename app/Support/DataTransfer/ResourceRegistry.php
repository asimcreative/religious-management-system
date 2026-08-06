<?php

namespace App\Support\DataTransfer;

use App\Exceptions\ResourceNotRegisteredException;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * The catalogue of every module the transfer engine can serve.
 *
 * Definition classes are listed in config/data-transfer.php. Registering a
 * new module — including one whose CRUD pages do not exist yet — is one line
 * there plus one definition class.
 */
class ResourceRegistry
{
    /** @var array<string, ResourceDefinitionContract>|null */
    private ?array $resolved = null;

    /** @param  array<int, class-string<ResourceDefinitionContract>>  $definitions */
    public function __construct(
        private readonly Container $container,
        private readonly array $definitions,
    ) {}

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @throws ResourceNotRegisteredException
     */
    public function get(string $key): ResourceDefinitionContract
    {
        return $this->all()[$key] ?? throw new ResourceNotRegisteredException($key);
    }

    /** @return array<string, ResourceDefinitionContract> */
    public function all(): array
    {
        return $this->resolved ??= $this->resolve();
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->all());
    }

    /**
     * Definitions that accept uploads, for the import history filter.
     *
     * @return array<string, ResourceDefinitionContract>
     */
    public function importable(): array
    {
        return array_filter(
            $this->all(),
            static fn (ResourceDefinitionContract $definition): bool => $definition->supportsImport(),
        );
    }

    /** @return array<string, ResourceDefinitionContract> */
    private function resolve(): array
    {
        $resolved = [];

        foreach ($this->definitions as $class) {
            $definition = $this->container->make($class);

            if (! $definition instanceof ResourceDefinitionContract) {
                throw new InvalidArgumentException(
                    "[{$class}] must implement ".ResourceDefinitionContract::class.'.'
                );
            }

            $key = $definition->key();

            if (isset($resolved[$key])) {
                throw new InvalidArgumentException(
                    "Duplicate data-transfer resource key [{$key}] declared by [{$class}]."
                );
            }

            $resolved[$key] = $definition;
        }

        return $resolved;
    }
}
