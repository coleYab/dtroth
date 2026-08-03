<?php

namespace App\Exceptions\IdentityProvider;

/**
 * The identity provider declined the presented identity.
 */
class ProviderDeclinedException extends IdentityProviderException
{
    public function __construct(
        string $message = 'The national ID presented is not valid for verification at this time.',
    ) {
        parent::__construct('identity_provider_declined', $message);
    }

    public function statusCode(): int
    {
        return 403;
    }
}
