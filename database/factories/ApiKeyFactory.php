<?php

namespace Database\Factories;

use App\Enums\EnvironmentName;
use App\Models\ApiKey;
use App\Models\App;
use App\Models\AppEnvironment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publicKey = 'pk_sandbox_'.Str::random(40);
        $secret = 'sk_sandbox_'.Str::random(48);

        return [
            'app_id' => App::factory(),
            'app_environment_id' => null,
            'name' => fake()->words(2, true),
            'public_key' => $publicKey,
            'secret_hash' => hash('sha256', $secret),
            'secret_preview' => substr($secret, 0, 7).'••••••••'.substr($secret, -4),
            'expires_at' => fake()->optional(0.5)->dateTimeBetween('+30 days', '+1 year'),
            'last_used_at' => fake()->optional(0.7)->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * Indicate the environment the key belongs to.
     */
    public function forEnvironment(AppEnvironment $environment): static
    {
        return $this->state(fn (array $attributes) => [
            'app_id' => $environment->app_id,
            'app_environment_id' => $environment->id,
            'public_key' => 'pk_'.$environment->name->value.'_'.Str::random(40),
            'secret_preview' => 'sk_'.$environment->name->value.'_••••••••'.Str::random(4),
        ]);
    }

    /**
     * Indicate that the key expires within the next 30 days.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the key never expires.
     */
    public function neverExpiring(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the key has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Generate a fresh public/secret key pair for the given environment.
     *
     * @return array{public_key: string, secret_key: string, secret_hash: string, secret_preview: string}
     */
    public static function generatePair(EnvironmentName $environment): array
    {
        $prefix = $environment->value;
        $publicKey = 'pk_'.$prefix.'_'.Str::random(40);
        $secret = 'sk_'.$prefix.'_'.Str::random(48);

        return [
            'public_key' => $publicKey,
            'secret_key' => $secret,
            'secret_hash' => hash('sha256', $secret),
            'secret_preview' => substr($secret, 0, 7).'••••••••'.substr($secret, -4),
        ];
    }
}
