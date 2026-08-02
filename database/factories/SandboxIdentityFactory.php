<?php

namespace Database\Factories;

use App\Models\SandboxIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SandboxIdentity>
 */
class SandboxIdentityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'sbx-'.fake()->unique()->slug(2),
            'label' => 'Test User '.fake()->firstName(),
            'age' => fake()->numberBetween(13, 40),
        ];
    }
}
