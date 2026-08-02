<?php

namespace Database\Factories;

use App\Enums\AgeQueryType;
use App\Models\App;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Verification>
 */
class VerificationFactory extends Factory
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
            'status' => fake()->randomElement(['approved', 'approved', 'declined', 'failed']),
            'query_type' => fake()->randomElement([AgeQueryType::EqualAbove, AgeQueryType::EqualAbove, AgeQueryType::EqualBelow]),
            'query_value' => fake()->randomElement([13, 16, 18, 21, 65]),
            'result' => fake()->boolean(70),
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * Indicate that the verification failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the verification was approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
