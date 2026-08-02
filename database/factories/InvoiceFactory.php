<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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
            'number' => 'INV-'.fake()->unique()->numerify('2026-####'),
            'tx_ref' => fake()->unique()->numerify('tx-########'),
            'amount' => fake()->randomElement([49900, 149900, 249900]),
            'currency' => 'ETB',
            'status' => Invoice::STATUS_PAID,
            'channel' => fake()->randomElement(['card', 'telebirr', 'cbe_birr']),
            'paid_at' => now()->subDays(fake()->numberBetween(1, 90)),
            'period_start' => now()->subMonth(),
            'period_end' => now(),
        ];
    }

    /**
     * Indicate a pending invoice.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PENDING,
            'paid_at' => null,
        ]);
    }
}
