<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => TeamRole::Developer,
            'accepted_at' => now(),
        ];
    }

    /**
     * Indicate the member's role.
     */
    public function role(TeamRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    /**
     * Indicate that the membership is still pending acceptance.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => null,
        ]);
    }
}
