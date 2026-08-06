<?php

namespace Database\Factories;

use App\Enums\AgeQueryType;
use App\Models\App;
use App\Models\Verification;
use App\Services\VerificationService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'query_id' => Str::uuid()->toString(),
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

    /**
     * Indicate a pending hosted-flow verification awaiting the subject.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Verification::STATUS_PENDING,
            'token' => Str::uuid()->toString(),
            'query_id' => $attributes['query_id'] ?? Str::uuid()->toString(),
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    /**
     * Indicate a completed hosted-flow verification.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Verification::STATUS_COMPLETED,
            'token' => Str::uuid()->toString(),
            'query_id' => $attributes['query_id'] ?? Str::uuid()->toString(),
            'expires_at' => now()->addDays(VerificationService::RESULT_TTL_DAYS),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate an expired hosted-flow verification.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Verification::STATUS_EXPIRED,
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}
