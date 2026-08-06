<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'name' => fake()->company().' Team',
            'slug' => fake()->unique()->slug(3).'-'.fake()->unique()->numberBetween(1000, 9999),
        ];
    }

    /**
     * Add the owner as an accepted member of the team.
     */
    public function withOwner(): static
    {
        return $this->afterCreating(function (Team $team) {
            $team->memberships()->create([
                'user_id' => $team->owner_user_id,
                'role' => TeamRole::Owner,
                'accepted_at' => now(),
            ]);
        });
    }
}
