<?php

namespace Database\Factories;

use App\Enums\ComponentStatus;
use App\Models\StatusCheck;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusCheck>
 */
class StatusCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'component' => 'verification-api',
            'status' => fake()->randomElement([ComponentStatus::Ok, ComponentStatus::Ok, ComponentStatus::Ok, ComponentStatus::Degraded]),
            'response_time_ms' => fake()->numberBetween(40, 1200),
            'checked_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * Indicate that the check was run on the given date.
     */
    public function on(CarbonInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_at' => $date,
        ]);
    }

    /**
     * Indicate the component that was checked.
     */
    public function forComponent(string $component): static
    {
        return $this->state(fn (array $attributes) => [
            'component' => $component,
        ]);
    }

    /**
     * Indicate an operational check.
     */
    public function ok(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComponentStatus::Ok,
        ]);
    }

    /**
     * Indicate a degraded check.
     */
    public function degraded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComponentStatus::Degraded,
        ]);
    }

    /**
     * Indicate a down check.
     */
    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComponentStatus::Down,
        ]);
    }
}
