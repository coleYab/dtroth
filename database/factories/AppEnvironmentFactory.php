<?php

namespace Database\Factories;

use App\Enums\EnvironmentName;
use App\Models\App;
use App\Models\AppEnvironment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppEnvironment>
 */
class AppEnvironmentFactory extends Factory
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
            'name' => fake()->randomElement(EnvironmentName::cases()),
            'is_enabled' => true,
            'is_live' => false,
            'redirect_uris' => ['https://example.com/callback'],
            'allowed_origins' => ['https://example.com'],
        ];
    }

    /**
     * Indicate that the environment is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Indicate that the environment is the live one.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_live' => true,
        ]);
    }
}
