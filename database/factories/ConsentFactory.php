<?php

namespace Database\Factories;

use App\Enums\AgeQueryType;
use App\Models\App;
use App\Models\Consent;
use App\Models\ConsentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consent>
 */
class ConsentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'subject_identifier' => fake()->uuid(),
            'query_type' => fake()->randomElement([AgeQueryType::EqualAbove, AgeQueryType::EqualAbove, AgeQueryType::EqualBelow]),
            'query_value' => fake()->randomElement([13, 16, 18, 21, 65]),
            'result' => fake()->boolean(70),
            'status' => Consent::STATUS_ACTIVE,
            'expires_at' => now()->addMonths(6),
        ];
    }

    /**
     * Indicate that the consent is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Consent::STATUS_ACTIVE,
            'revoked_at' => null,
            'expires_at' => now()->addMonths(6),
        ]);
    }

    /**
     * Indicate that the consent has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Consent::STATUS_REVOKED,
            'revoked_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }

    /**
     * Indicate that the consent has naturally expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Consent::STATUS_EXPIRED,
            'expires_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }

    /**
     * Ensure a granted event exists for the consent.
     */
    public function withEvents(): static
    {
        return $this->afterCreating(function (Consent $consent): void {
            ConsentEvent::factory()->granted()->create([
                'consent_id' => $consent->id,
            ]);
        });
    }
}
