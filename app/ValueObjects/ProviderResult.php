<?php

namespace App\ValueObjects;

use DateTimeImmutable;

/**
 * The outcome of an identity provider lookup.
 *
 * Privacy-first: the result carries only the boolean verdict and a
 * provider-side reference — never birth dates, ages, or names.
 */
final readonly class ProviderResult
{
    public function __construct(
        public bool $result,
        public string $reference,
        public DateTimeImmutable $verifiedAt,
        public ?string $rawProviderData = null,
    ) {}
}
