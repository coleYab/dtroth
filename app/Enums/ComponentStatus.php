<?php

namespace App\Enums;

enum ComponentStatus: string
{
    case Ok = 'ok';
    case Degraded = 'degraded';
    case Down = 'down';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ok => 'Operational',
            self::Degraded => 'Degraded',
            self::Down => 'Down',
        };
    }
}
