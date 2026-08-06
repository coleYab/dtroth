<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Invitation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => TeamRole::Developer,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(Invitation::VALID_DAYS),
        ];
    }

    /**
     * Indicate that the invitation has already been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate that the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
