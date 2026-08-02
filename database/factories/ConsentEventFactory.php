<?php

namespace Database\Factories;

use App\Models\Consent;
use App\Models\ConsentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentEvent>
 */
class ConsentEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consent_id' => Consent::factory(),
            'type' => ConsentEvent::TYPE_GRANTED,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the consent was granted.
     */
    public function granted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ConsentEvent::TYPE_GRANTED,
        ]);
    }

    /**
     * Indicate that the consent was revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ConsentEvent::TYPE_REVOKED,
        ]);
    }
}
