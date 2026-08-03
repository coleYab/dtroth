<?php

use App\Contracts\IdentityProvider;
use App\Enums\AgeQueryType;
use App\Exceptions\IdentityProvider\IdentityProviderException;
use App\Exceptions\IdentityProvider\ProviderDeclinedException;
use App\Exceptions\IdentityProvider\ProviderTimeoutException;
use App\Exceptions\IdentityProvider\ProviderUnreachableException;
use App\Exceptions\IdentityProvider\SubjectNotFoundException;
use App\Models\ProviderIdentity;
use App\Models\SandboxIdentity;
use App\Providers\Identity\MockFaydaProvider;
use App\Providers\Identity\SandboxIdentityProvider;
use App\Services\IdentityProviderRegistry;
use App\ValueObjects\IdentityQuery;

function resolveProvider(string $key): IdentityProvider
{
    return app(IdentityProviderRegistry::class)->resolveProvider($key);
}

test('the mock provider resolves a known national id for equal_above queries', function () {
    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $result = $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualAbove));

    expect($result->result)->toBeTrue()
        ->and($result->reference)->toStartWith('fayda-');
});

test('the mock provider returns false when the subject is below the threshold', function () {
    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(16)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $result = $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualAbove));

    expect($result->result)->toBeFalse();
});

test('the mock provider handles equal_below queries', function () {
    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(16)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $result = $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualBelow));

    expect($result->result)->toBeTrue();
});

test('the mock provider treats an exact age match as passing', function () {
    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(18)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $result = $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualAbove));

    expect($result->result)->toBeTrue();
});

test('the mock provider normalizes national id formatting', function () {
    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $result = $provider->verify(new IdentityQuery('185 026 948 372 154', 18, AgeQueryType::EqualAbove));

    expect($result->result)->toBeTrue();
});

test('the mock provider rejects an unknown national id', function () {
    $provider = resolveProvider('mock_fayda');

    expect(fn () => $provider->verify(new IdentityQuery('000000000000000', 18, AgeQueryType::EqualAbove)))
        ->toThrow(SubjectNotFoundException::class);
});

test('the mock provider declines blocked and lost identities', function (string $status) {
    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => $status,
    ]);

    expect(fn () => $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualAbove)))
        ->toThrow(ProviderDeclinedException::class);
})->with([
    'blocked' => ['blocked'],
    'lost' => ['lost'],
]);

test('provider results never expose birth data or names', function () {
    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'full_name' => 'Hana Bekele',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    $result = $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualAbove));

    expect($result->result)->toBeBool()
        ->and($result->reference)->toBeString()
        ->and($result->verifiedAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and((array) $result)->toHaveCount(4)
        ->and(array_keys((array) $result))->not->toContain('birth_date')
        ->and(array_keys((array) $result))->not->toContain('full_name');
});

test('the mock provider throws unreachable or timeout exceptions at a full failure rate', function () {
    config()->set('verify.providers.mock_fayda.failure_rate', 1.0);
    config()->set('verify.providers.mock_fayda.latency_min_ms', 0);
    config()->set('verify.providers.mock_fayda.latency_max_ms', 0);

    $provider = resolveProvider('mock_fayda');
    ProviderIdentity::factory()->create([
        'national_id' => '185026948372154',
        'birth_date' => now()->subYears(23)->format('Y-m-d'),
        'status' => 'active',
    ]);

    try {
        $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualAbove));

        test()->fail('Expected a provider failure at a 100% failure rate.');
    } catch (IdentityProviderException $e) {
        expect(in_array($e::class, [ProviderUnreachableException::class, ProviderTimeoutException::class], true))->toBeTrue();
    }
});

test('the mock provider times out when the simulated latency exceeds the timeout', function () {
    config()->set('verify.providers.mock_fayda.latency_max_ms', 500);
    config()->set('verify.providers.mock_fayda.timeout_ms', 200);

    $provider = resolveProvider('mock_fayda');

    expect(fn () => $provider->verify(new IdentityQuery('185026948372154', 18, AgeQueryType::EqualAbove)))
        ->toThrow(ProviderTimeoutException::class);
});

test('the sandbox provider resolves mock test identities', function () {
    $provider = resolveProvider('sandbox');
    SandboxIdentity::create(['key' => 'fayda-test-a', 'label' => 'Test User A', 'age' => 20]);

    $result = $provider->verify(new IdentityQuery('fayda-test-a', 18, AgeQueryType::EqualAbove));

    expect($result->result)->toBeTrue()
        ->and($result->reference)->toStartWith('sbx-');
});

test('the sandbox provider rejects unknown subjects with the sandbox error code', function () {
    $provider = resolveProvider('sandbox');

    expect(fn () => $provider->verify(new IdentityQuery('does-not-exist', 18, AgeQueryType::EqualAbove)))
        ->toThrow(SubjectNotFoundException::class);
});

test('the sandbox provider maps unknown subjects to unknown_subject', function () {
    $provider = resolveProvider('sandbox');

    try {
        $provider->verify(new IdentityQuery('does-not-exist', 18, AgeQueryType::EqualAbove));
    } catch (SubjectNotFoundException $e) {
        expect($e->errorCode)->toBe('unknown_subject')
            ->and($e->statusCode())->toBe(404);

        return;
    }

    test()->fail('Expected a SubjectNotFoundException.');
});

test('both providers report availability through the contract', function () {
    expect(app(MockFaydaProvider::class)->isAvailable())->toBeTrue()
        ->and(app(SandboxIdentityProvider::class)->isAvailable())->toBeTrue()
        ->and(app(MockFaydaProvider::class)->name())->toBe('mock_fayda')
        ->and(app(SandboxIdentityProvider::class)->name())->toBe('sandbox');
});
