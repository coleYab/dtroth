<?php

namespace Database\Factories;

use App\Enums\IncidentStatus;
use App\Models\IncidentUpdate;
use App\Models\StatusIncident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusIncident>
 */
class StatusIncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'component' => fake()->randomElement(['verification-api', 'webhook-delivery', null]),
            'started_at' => fake()->dateTimeBetween('-60 days'),
            'resolved_at' => null,
        ];
    }

    /**
     * Indicate a resolved incident with a full update timeline.
     */
    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = fake()->dateTimeBetween('-60 days', '-2 days');

            return [
                'started_at' => $startedAt,
                'resolved_at' => fake()->dateTimeBetween($startedAt, '+2 days'),
            ];
        })->afterCreating(function (StatusIncident $incident) {
            $timeline = [
                [IncidentStatus::Investigating, 'We are investigating reports of elevated error rates.'],
                [IncidentStatus::Identified, 'The issue has been traced to a downstream provider; workaround in place.'],
                [IncidentStatus::Monitoring, 'A fix has been deployed; we are monitoring results.'],
                [IncidentStatus::Resolved, 'The incident has been fully resolved.'],
            ];

            $cursor = $incident->started_at;

            foreach ($timeline as [$status, $message]) {
                $cursor = $cursor->copy()->addMinutes(fake()->numberBetween(15, 180));

                IncidentUpdate::create([
                    'incident_id' => $incident->id,
                    'status' => $status,
                    'message' => $message,
                    'created_at' => $cursor,
                ]);
            }
        });
    }
}
