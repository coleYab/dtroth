<?php

namespace Database\Factories;

use App\Enums\AgeQueryType;
use App\Models\AgeQueryPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgeQueryPreset>
 */
class AgeQueryPresetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => AgeQueryType::EqualAbove,
            'value' => 18,
            'label' => '18+',
            'description' => 'Require the user to be at least 18 years old.',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
