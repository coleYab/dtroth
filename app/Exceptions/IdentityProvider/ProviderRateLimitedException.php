<?php

namespace App\Exceptions\IdentityProvider;

/**
 * The identity provider is rate-limiting requests.
 */
class ProviderRateLimitedException extends IdentityProviderException
{
    public function __construct(
        string $message = 'The identity provider has received too many requests. Please try again shortly.',
    ) {
        parent::__construct('identity_provider_rate_limited', $message);
    }

    public function statusCode(): int
    {
        return 429;
    }
}
