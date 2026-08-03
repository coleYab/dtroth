<?php

namespace App\Contracts;

use App\Exceptions\IdentityProvider\IdentityProviderException;
use App\ValueObjects\IdentityQuery;
use App\ValueObjects\ProviderResult;

/**
 * Resolves an age query against a national identity registry.
 *
 * Implementations are interchangeable: the sandbox adapter resolves mock
 * test identities, the production provider resolves real national IDs, and
 * a future integration can be dropped in without touching callers.
 */
interface IdentityProvider
{
    /**
     * The provider's machine-readable name, used in logs and references.
     */
    public function name(): string;

    /**
     * Whether the provider is currently reachable and accepting requests.
     */
    public function isAvailable(): bool;

    /**
     * Resolve the query against the provider's identity registry.
     *
     * @throws IdentityProviderException
     */
    public function verify(IdentityQuery $query): ProviderResult;
}
