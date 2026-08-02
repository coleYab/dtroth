<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
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
            'app_id' => null,
            'action' => AuditLog::ACTION_KEY_CREATED,
            'description' => fake()->sentence(),
            'created_at' => now()->subMinutes(fake()->numberBetween(1, 1440)),
        ];
    }

    /**
     * Indicate that the event relates to an application.
     */
    public function forApp(App $app): static
    {
        return $this->state(fn (array $attributes) => [
            'app_id' => $app->id,
        ]);
    }

    /**
     * Set the action performed.
     */
    public function ofAction(string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
        ]);
    }
}
