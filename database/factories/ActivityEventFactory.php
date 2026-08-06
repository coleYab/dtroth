<?php

namespace Database\Factories;

use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\App;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityEvent>
 */
class ActivityEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var ActivityEventType $type */
        $type = fake()->randomElement(ActivityEventType::cases());

        return [
            'app_id' => App::factory(),
            'type' => $type,
            'description' => match ($type) {
                ActivityEventType::KeyRotation => 'API key rotated',
                ActivityEventType::FailedVerification => 'Verification request failed (invalid document)',
                ActivityEventType::ScopeChange => 'Requested scopes updated',
            },
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * Indicate the event type.
     */
    public function ofType(ActivityEventType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Indicate that the event occurred on the given date.
     */
    public function on(CarbonInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $date,
        ]);
    }
}
