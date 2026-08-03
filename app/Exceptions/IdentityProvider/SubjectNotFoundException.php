<?php

namespace App\Exceptions\IdentityProvider;

/**
 * The subject does not match a known identity.
 */
class SubjectNotFoundException extends IdentityProviderException
{
    public function __construct(
        string $errorCode = 'subject_not_found',
        string $message = 'The provided subject does not match a known identity.',
    ) {
        parent::__construct($errorCode, $message);
    }

    public function statusCode(): int
    {
        return 404;
    }
}
