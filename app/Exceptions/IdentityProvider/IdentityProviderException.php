<?php

namespace App\Exceptions\IdentityProvider;

use RuntimeException;

/**
 * Base class for failures raised by an identity provider.
 * Carries the machine-readable error code and the HTTP status the failure
 * should surface as on the public API.
 */
abstract class IdentityProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * The HTTP status this failure maps to on the public API.
     */
    abstract public function statusCode(): int;
}
