<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\VerificationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationSetting>
 */
class VerificationSettingFactory extends Factory
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
            'consent_text' => VerificationSetting::DEFAULT_CONSENT_TEXT,
            're_verify_months' => 6,
        ];
    }
}
