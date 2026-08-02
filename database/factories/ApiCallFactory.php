<?php

namespace Database\Factories;

use App\Enums\AgeQueryType;
use App\Models\ApiCall;
use App\Models\App;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiCall>
 */
class ApiCallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $queryType = fake()->randomElement([AgeQueryType::EqualAbove, AgeQueryType::EqualAbove, AgeQueryType::EqualBelow]);

        return [
            'app_id' => App::factory(),
            'method' => fake()->randomElement(['GET', 'POST']),
            'path' => fake()->randomElement([
                '/v1/verify',
                '/v1/consents',
                '/v1/age-check',
            ]),
            'query_type' => $queryType,
            'query_value' => fake()->randomElement([13, 16, 18, 21, 65]),
            'result' => fake()->boolean(70),
            'status_code' => fake()->randomElement([200, 200, 200, 201, 400, 401, 403, 429]),
            'response_time_ms' => fake()->numberBetween(40, 900),
            'request_payload' => ['user' => fake()->uuid(), 'query' => $queryType->value, 'value' => 18],
            'response_payload' => ['result' => fake()->boolean(70)],
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * Indicate that the call was made on the given date.
     */
    public function on(CarbonInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $date,
        ]);
    }

    /**
     * Indicate a successful call with a verified query result.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => fake()->randomElement([200, 201]),
        ]);
    }

    /**
     * Indicate a failing call.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => fake()->randomElement([400, 401, 403, 429]),
        ]);
    }
}
