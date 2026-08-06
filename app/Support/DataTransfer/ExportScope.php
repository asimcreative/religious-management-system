<?php

namespace App\Support\DataTransfer;

/**
 * How much of the list an export covers.
 *
 * Every scope is applied on top of the tenant-scoped, role-scoped base query —
 * a scope widens what the user asked for, never what they are allowed to see.
 */
enum ExportScope: string
{
    /** Only the rows visible on the page the user is looking at. */
    case Page = 'page';

    /** Every row the user may see, ignoring the current filters. */
    case All = 'all';

    /** Every row matching the filters currently applied to the list. */
    case Filtered = 'filtered';

    /** Only the rows the user ticked. */
    case Selected = 'selected';

    public function label(): string
    {
        return __('data_transfer.scopes.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Page => 'bi-file-earmark',
            self::All => 'bi-collection',
            self::Filtered => 'bi-funnel',
            self::Selected => 'bi-check2-square',
        };
    }

    /** Whether this scope needs the caller to supply record IDs. */
    public function requiresIds(): bool
    {
        return $this === self::Selected;
    }

    /** Whether the list's current filters apply. */
    public function honoursFilters(): bool
    {
        return $this === self::Filtered || $this === self::Page;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
