<?php

namespace App\Exceptions\IdentityProvider;

/**
 * The identity provider could not be reached.
 */
class ProviderUnreachableException extends IdentityProviderException
{
    public function __construct(
        string $message = 'The identity provider could not be reached. Please try again later.',
    ) {
        parent::__construct('identity_provider_unreachable', $message);
    }

    public function statusCode(): int
    {
        return 502;
    }
}
