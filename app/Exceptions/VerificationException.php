<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an age-verification request cannot be satisfied.
 * Carries the HTTP status and machine-readable error code.
 */
class VerificationException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
