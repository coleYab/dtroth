<?php

namespace App\Exceptions\IdentityProvider;

/**
 * The identity provider took too long to respond.
 */
class ProviderTimeoutException extends IdentityProviderException
{
    public function __construct(
        string $message = 'The identity provider took too long to respond. Please try again later.',
    ) {
        parent::__construct('identity_provider_timeout', $message);
    }

    public function statusCode(): int
    {
        return 504;
    }
}
