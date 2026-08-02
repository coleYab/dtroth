<?php

namespace App\Enums;

enum AgeQueryType: string
{
    case EqualAbove = 'equal_above';
    case EqualBelow = 'equal_below';

    /**
     * Human-readable label for the operator.
     */
    public function label(): string
    {
        return match ($this) {
            self::EqualAbove => 'Equal or above',
            self::EqualBelow => 'Equal or below',
        };
    }

    /**
     * The readable query string, e.g. "18+".
     */
    public function describe(int $value): string
    {
        return match ($this) {
            self::EqualAbove => "{$value}+",
            self::EqualBelow => "{$value}-",
        };
    }
}
