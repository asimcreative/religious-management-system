<?php

namespace App\Exceptions;

use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Raised when a transfer route names a module the registry does not know.
 *
 * Rendered as a 404 rather than a 500: an unknown resource key is a bad URL,
 * not a server fault, and answering 404 avoids confirming which keys exist.
 */
class ResourceNotRegisteredException extends RuntimeException implements HttpExceptionInterface
{
    public function __construct(
        public readonly string $resourceKey,
    ) {
        parent::__construct("No data-transfer resource is registered for [{$resourceKey}].");
    }

    public function getStatusCode(): int
    {
        return 404;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return [];
    }
}
