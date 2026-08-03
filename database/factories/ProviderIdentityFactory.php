<?php

namespace Database\Factories;

use App\Models\ProviderIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderIdentity>
 */
class ProviderIdentityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'national_id' => fake()->unique()->numerify('###############'),
            'full_name' => fake()->name(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-13 years')->format('Y-m-d'),
            'status' => ProviderIdentity::STATUS_ACTIVE,
        ];
    }

    /**
     * The identity is valid for verification.
     */
    public function active(): static
    {
        return $this->state(fn (): array => ['status' => ProviderIdentity::STATUS_ACTIVE]);
    }

    /**
     * The identity is blocked and cannot be verified.
     */
    public function blocked(): static
    {
        return $this->state(fn (): array => ['status' => ProviderIdentity::STATUS_BLOCKED]);
    }

    /**
     * The identity is reported lost and cannot be verified.
     */
    public function lost(): static
    {
        return $this->state(fn (): array => ['status' => ProviderIdentity::STATUS_LOST]);
    }

    /**
     * An identity whose age is exactly the given value today.
     */
    public function aged(int $years): static
    {
        $birthDate = now()->subYears($years);

        return $this->state(fn (): array => ['birth_date' => $birthDate->format('Y-m-d')]);
    }
}
