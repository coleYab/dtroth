<?php

use App\Enums\EnvironmentName;
use App\Providers\Identity\MockFaydaProvider;
use App\Providers\Identity\SandboxIdentityProvider;
use App\Services\IdentityProviderRegistry;
use RuntimeException;
use stdClass;

test('the registry resolves the sandbox adapter for sandbox environments', function () {
    $registry = app(IdentityProviderRegistry::class);

    expect($registry->resolve(EnvironmentName::Sandbox))->toBeInstanceOf(SandboxIdentityProvider::class);
});

test('the registry resolves the configured provider for production environments', function () {
    config()->set('verify.provider', 'mock_fayda');

    $registry = app(IdentityProviderRegistry::class);

    expect($registry->resolve(EnvironmentName::Production))->toBeInstanceOf(MockFaydaProvider::class);
});

test('the registry honors the configured provider switch for production', function () {
    config()->set('verify.provider', 'sandbox');

    $registry = app(IdentityProviderRegistry::class);

    expect($registry->resolve(EnvironmentName::Production))->toBeInstanceOf(SandboxIdentityProvider::class);
});

test('the registry throws when the configured provider class is invalid', function () {
    config()->set('verify.provider', 'broken');
    config()->set('verify.providers.broken', ['class' => stdClass::class]);

    app(IdentityProviderRegistry::class)->resolve(EnvironmentName::Production);
})->throws(RuntimeException::class);

test('the registry throws when the configured provider is missing', function () {
    config()->set('verify.provider', 'missing_provider');

    app(IdentityProviderRegistry::class)->resolve(EnvironmentName::Production);
})->throws(RuntimeException::class);

test('the registry caches resolved providers per registry instance', function () {
    $registry = app(IdentityProviderRegistry::class);

    expect($registry->resolve(EnvironmentName::Sandbox))->toBe($registry->resolve(EnvironmentName::Sandbox));
});
