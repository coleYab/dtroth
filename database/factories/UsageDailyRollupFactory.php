<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\UsageDailyRollup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageDailyRollup>
 */
class UsageDailyRollupFactory extends Factory
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
            'date' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),
            'calls_total' => fake()->numberBetween(10, 500),
            'verifications_total' => fake()->numberBetween(1, 120),
            'failed_calls' => fake()->numberBetween(0, 40),
            'success_rate' => fake()->randomFloat(2, 85, 100),
            'most_used_query' => '18+',
        ];
    }
}
