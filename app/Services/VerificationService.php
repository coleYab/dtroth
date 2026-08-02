<?php

namespace App\Services;

use App\Enums\AgeQueryType;
use App\Enums\EnvironmentName;
use App\Exceptions\VerificationException;
use App\Models\AgeQueryPreset;
use App\Models\ApiCall;
use App\Models\ApiKey;
use App\Models\SandboxIdentity;
use Illuminate\Support\Str;

/**
 * Resolves an age-query against a subject identity and records the call.
 * This is the single source of truth used by both the public API and the
 * sandbox so the two never drift apart.
 */
class VerificationService
{
    /**
     * How long a verified result remains valid before it expires.
     */
    public const RESULT_TTL_DAYS = 90;

    /**
     * Authenticate the caller with the bearer secret and return their key.
     *
     * @throws VerificationException
     */
    public function authenticate(string $secret): ApiKey
    {
        $key = ApiKey::query()
            ->where('secret_hash', hash('sha256', $secret))
            ->with('environment')
            ->first();

        if ($key === null || $key->isRevoked()) {
            throw new VerificationException(401, 'invalid_key', 'The provided API key is invalid or has been revoked.');
        }

        if ($key->expires_at !== null && $key->expires_at->isPast()) {
            throw new VerificationException(401, 'invalid_key', 'The provided API key has expired.');
        }

        if ($key->environment === null) {
            throw new VerificationException(401, 'invalid_key', 'The provided API key is not attached to an environment.');
        }

        $key->forceFill(['last_used_at' => now()])->saveQuietly();

        return $key;
    }

    /**
     * Verify an age query against the given environment and subject.
     *
     * @return array{result: bool, query_id: string, verified_at: string, expires_at: string}
     *
     * @throws VerificationException
     */
    public function verify(ApiKey $key, int $age, AgeQueryType $type, string $subject): array
    {
        $environment = $key->environment;

        if ($environment->name !== EnvironmentName::Sandbox) {
            throw new VerificationException(501, 'identity_provider_unavailable', 'Verification for this environment is not available yet. Use a sandbox key to test the API.');
        }

        $this->ensurePresetAllowed($key->app_id, $type, $age);

        $identity = SandboxIdentity::query()->where('key', $subject)->first();

        if ($identity === null) {
            throw new VerificationException(404, 'unknown_subject', 'The provided subject does not match a known test identity.');
        }

        $result = match ($type) {
            AgeQueryType::EqualAbove => $identity->age >= $age,
            AgeQueryType::EqualBelow => $identity->age <= $age,
        };

        $verifiedAt = now();
        $expiresAt = $verifiedAt->copy()->addDays(self::RESULT_TTL_DAYS);

        return [
            'result' => $result,
            'query_id' => (string) Str::uuid(),
            'verified_at' => $verifiedAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Record the outcome of a call for usage and logging purposes.
     */
    public function recordCall(
        ApiKey $key,
        int $age,
        AgeQueryType $type,
        string $subject,
        array $payload,
        int $statusCode,
        int $responseTimeMs,
    ): void {
        ApiCall::create([
            'app_id' => $key->app_id,
            'method' => 'POST',
            'path' => '/v1/verify/age',
            'query_type' => $type,
            'query_value' => $age,
            'result' => $payload['result'] ?? null,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'request_payload' => ['age' => $age, 'type' => $type->value, 'subject' => $subject],
            'response_payload' => $payload,
            'created_at' => now(),
        ]);
    }

    /**
     * Ensure the app has enabled a preset matching the requested query.
     *
     * @throws VerificationException
     */
    private function ensurePresetAllowed(int $appId, AgeQueryType $type, int $age): void
    {
        $allowed = AgeQueryPreset::query()
            ->where('type', $type)
            ->where('value', $age)
            ->whereHas('apps', fn ($query) => $query->whereKey($appId))
            ->exists();

        if (! $allowed) {
            throw new VerificationException(403, 'preset_not_allowed', sprintf('No enabled preset matches the query "%s".', $type->describe($age)));
        }
    }
}
