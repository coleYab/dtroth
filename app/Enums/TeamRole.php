<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';

    case Developer = 'developer';

    case Viewer = 'viewer';

    /**
     * A human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Developer => 'Developer',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * Whether this role is at least as privileged as the given role.
     */
    public function atLeast(TeamRole $role): bool
    {
        $rank = [
            self::Viewer->value => 0,
            self::Developer->value => 1,
            self::Owner->value => 2,
        ];

        return $rank[$this->value] >= $rank[$role->value];
    }
}
