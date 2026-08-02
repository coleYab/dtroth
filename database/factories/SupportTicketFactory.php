<?php

namespace Database\Factories;

use App\Enums\SupportTicketCategory;
use App\Models\App;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
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
            'subject' => fake()->sentence(5),
            'category' => fake()->randomElement(SupportTicketCategory::cases()),
            'app_id' => null,
            'description' => fake()->paragraph(2),
            'attachment_path' => null,
            'status' => 'new',
            'reference' => 'TKT-'.strtoupper(fake()->unique()->bothify('########')),
        ];
    }

    /**
     * Associate the ticket with an application.
     */
    public function forApp(App $app): static
    {
        return $this->state(fn (array $attributes) => [
            'app_id' => $app->id,
        ]);
    }
}
