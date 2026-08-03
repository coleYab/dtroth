<?php

namespace App\Providers\Identity;

use App\Contracts\IdentityProvider;
use App\Enums\AgeQueryType;
use App\Exceptions\IdentityProvider\ProviderDeclinedException;
use App\Exceptions\IdentityProvider\ProviderTimeoutException;
use App\Exceptions\IdentityProvider\ProviderUnreachableException;
use App\Exceptions\IdentityProvider\SubjectNotFoundException;
use App\Models\ProviderIdentity;
use App\ValueObjects\IdentityQuery;
use App\ValueObjects\ProviderResult;
use Illuminate\Support\Str;

/**
 * A production-grade simulation of the national ID (Fayda) provider.
 *
 * Resolves subjects against the provider_identities registry, behaves like a
 * real network backend (simulated latency and failure modes), and never
 * returns birth data — only a boolean verdict and a provider reference.
 */
class MockFaydaProvider implements IdentityProvider
{
    /**
     * The provider's machine-readable name.
     */
    public function name(): string
    {
        return 'mock_fayda';
    }

    /**
     * The mock provider is always reachable.
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Resolve the query against the simulated national identity registry.
     *
     * @throws SubjectNotFoundException
     * @throws ProviderDeclinedException
     * @throws ProviderTimeoutException
     * @throws ProviderUnreachableException
     */
    public function verify(IdentityQuery $query): ProviderResult
    {
        $this->simulateProviderConditions();

        $identity = ProviderIdentity::query()
            ->where('national_id', $this->normalizeNationalId($query->subject))
            ->first();

        if ($identity === null) {
            throw new SubjectNotFoundException;
        }

        if (! $identity->isUsable()) {
            throw new ProviderDeclinedException;
        }

        $age = $identity->birth_date->age;

        $result = match ($query->type) {
            AgeQueryType::EqualAbove => $age >= $query->age,
            AgeQueryType::EqualBelow => $age <= $query->age,
        };

        return new ProviderResult(
            result: $result,
            reference: 'fayda-'.Str::uuid(),
            verifiedAt: now()->toImmutable(),
        );
    }

    /**
     * Normalize a national ID for lookup: strip separators and casing.
     */
    private function normalizeNationalId(string $nationalId): string
    {
        return strtoupper(preg_replace('/[\s-]+/', '', $nationalId) ?? '');
    }

    /**
     * Simulate network conditions: latency, timeouts, and random failures.
     * Configuration is read at runtime so tests can override per request.
     */
    private function simulateProviderConditions(): void
    {
        $config = config('verify.providers.mock_fayda');

        $min = (int) ($config['latency_min_ms'] ?? 40);
        $max = (int) ($config['latency_max_ms'] ?? 120);
        $timeout = (int) ($config['timeout_ms'] ?? 1000);
        $failureRate = (float) ($config['failure_rate'] ?? 0.0);

        if ($max > $timeout) {
            throw new ProviderTimeoutException;
        }

        usleep(random_int($min, max($min, $max)) * 1000);

        if (mt_rand() / mt_getrandmax() < $failureRate) {
            if (random_int(0, 1) === 0) {
                throw new ProviderUnreachableException;
            }

            throw new ProviderTimeoutException;
        }
    }
}
