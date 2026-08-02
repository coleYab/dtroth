<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $secret = 'whsec_'.str()->random(32);

        return [
            'app_id' => App::factory(),
            'url' => fake()->url(),
            'secret' => $secret,
            'secret_preview' => 'whsec_••••••••'.substr($secret, -4),
            'description' => fake()->optional()->sentence(),
            'last_triggered_at' => null,
        ];
    }
}
