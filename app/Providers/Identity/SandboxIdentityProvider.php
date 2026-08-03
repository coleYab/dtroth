<?php

namespace App\Providers\Identity;

use App\Contracts\IdentityProvider;
use App\Enums\AgeQueryType;
use App\Exceptions\IdentityProvider\SubjectNotFoundException;
use App\Models\SandboxIdentity;
use App\ValueObjects\IdentityQuery;
use App\ValueObjects\ProviderResult;
use Illuminate\Support\Str;

/**
 * Resolves age queries against the mock test identities used by sandbox
 * environments. Behavior is identical in spirit to the production provider
 * so sandbox and production never drift apart.
 */
class SandboxIdentityProvider implements IdentityProvider
{
    /**
     * The provider's machine-readable name.
     */
    public function name(): string
    {
        return 'sandbox';
    }

    /**
     * The sandbox adapter is always reachable.
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Resolve the query against a known test identity.
     *
     * @throws SubjectNotFoundException
     */
    public function verify(IdentityQuery $query): ProviderResult
    {
        $identity = SandboxIdentity::query()->where('key', $query->subject)->first();

        if ($identity === null) {
            throw new SubjectNotFoundException(
                errorCode: 'unknown_subject',
                message: 'The provided subject does not match a known test identity.',
            );
        }

        $result = match ($query->type) {
            AgeQueryType::EqualAbove => $identity->age >= $query->age,
            AgeQueryType::EqualBelow => $identity->age <= $query->age,
        };

        return new ProviderResult(
            result: $result,
            reference: 'sbx-'.Str::uuid(),
            verifiedAt: now()->toImmutable(),
        );
    }
}
