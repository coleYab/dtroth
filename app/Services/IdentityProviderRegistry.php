<?php

namespace App\Services;

use App\Contracts\IdentityProvider;
use App\Enums\EnvironmentName;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Resolves the identity provider for an environment.
 *
 * Sandbox environments always use the sandbox adapter; production
 * environments use whatever provider is configured in verify.provider.
 * Providers are resolved through the container so they can be swapped,
 * mocked, or configured in one place.
 */
class IdentityProviderRegistry
{
    /**
     * Providers already resolved for this request lifecycle.
     *
     * @var array<string, IdentityProvider>
     */
    private array $resolved = [];

    public function __construct(private readonly Container $container) {}

    /**
     * Resolve the identity provider for the given environment.
     */
    public function resolve(EnvironmentName $environment): IdentityProvider
    {
        return match ($environment) {
            EnvironmentName::Sandbox => $this->resolveProvider('sandbox'),
            EnvironmentName::Production => $this->resolveProvider((string) config('verify.provider')),
        };
    }

    /**
     * Resolve (and cache) a provider by its config key.
     *
     * @throws RuntimeException
     */
    public function resolveProvider(string $key): IdentityProvider
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $config = config("verify.providers.$key");

        if ($config === null || ! isset($config['class'])) {
            throw new RuntimeException(sprintf('No identity provider is configured for "%s".', $key));
        }

        $class = $config['class'];

        if (! is_string($class)) {
            throw new RuntimeException(sprintf('Identity provider class for "%s" must be a string.', $key));
        }

        if (! is_subclass_of($class, IdentityProvider::class)) {
            throw new RuntimeException(sprintf('Identity provider "%s" must implement %s.', $class, IdentityProvider::class));
        }

        return $this->resolved[$key] = $this->container->make($class);
    }
}
