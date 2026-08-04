<?php

namespace App\Enums;

enum Status: int
{
    case Active = 1;
    case Inactive = 0;
    case Suspended = 2;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Inactive => 'bg-secondary',
            self::Suspended => 'bg-danger',
        };
    }
}
