<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\VerificationFlow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationFlow>
 */
class VerificationFlowFactory extends Factory
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
            'brand_color' => '#0f172a',
            'success_redirect_url' => 'https://example.com/success',
            'failure_redirect_url' => 'https://example.com/failure',
            'callback_url' => 'https://example.com/webhook/callback',
        ];
    }
}
