<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event' => 'verification.completed',
            'payload' => ['event' => 'verification.completed', 'data' => ['result' => true, 'age' => 18]],
            'response_status' => 200,
            'response_body' => 'ok',
            'attempts' => 1,
            'status' => WebhookDeliveryStatus::Success,
            'delivered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 500,
            'response_body' => 'Internal server error',
            'attempts' => 3,
            'status' => WebhookDeliveryStatus::Failed,
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the delivery is still being retried.
     */
    public function retrying(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => null,
            'response_body' => null,
            'attempts' => 2,
            'status' => WebhookDeliveryStatus::Retrying,
            'delivered_at' => null,
        ]);
    }
}
