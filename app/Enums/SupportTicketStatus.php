<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case New = 'new';
    case Open = 'open';
    case Resolved = 'resolved';

    /**
     * Human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Open => 'Open',
            self::Resolved => 'Resolved',
        };
    }
}
