<?php

namespace Database\Factories;

use App\Enums\AppStatus;
use App\Enums\EnvironmentName;
use App\Models\App;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<App>
 */
class AppFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'status' => AppStatus::Active,
        ];
    }

    /**
     * Indicate that the app is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppStatus::Disabled,
        ]);
    }

    /**
     * Create the sandbox and production environments for the app.
     */
    public function withEnvironments(): static
    {
        return $this->afterCreating(function (App $app) {
            $app->environments()->create([
                'name' => EnvironmentName::Sandbox,
                'redirect_uris' => ['https://example.com/callback'],
                'allowed_origins' => ['https://example.com'],
            ]);

            $app->environments()->create([
                'name' => EnvironmentName::Production,
                'is_live' => true,
                'redirect_uris' => ['https://example.com/callback'],
                'allowed_origins' => ['https://example.com'],
            ]);
        });
    }
}
