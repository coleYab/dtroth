<?php

namespace App\ValueObjects;

use App\Enums\AgeQueryType;

/**
 * The age query to resolve against an identity provider.
 */
final readonly class IdentityQuery
{
    public function __construct(
        public string $subject,
        public int $age,
        public AgeQueryType $type,
    ) {}
}
