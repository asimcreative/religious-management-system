<?php

namespace App\Support\DataTransfer;

/**
 * What a bulk submission is asking for.
 *
 * An enum rather than a validated string so the controller's match is
 * exhaustive: adding a third action becomes a compile-time obligation to
 * handle it, not a runtime surprise.
 */
enum BulkAction: string
{
    case Delete = 'delete';
    case Status = 'status';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
