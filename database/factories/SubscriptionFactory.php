<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
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
            'plan' => Subscription::PLAN_TRIAL,
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    /**
     * Indicate an active paid subscription.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'pro',
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'renews_at' => now()->addMonth(),
            'channel' => 'card',
            'tx_ref' => 'tx-'.fake()->unique()->numerify('########'),
        ]);
    }
}
