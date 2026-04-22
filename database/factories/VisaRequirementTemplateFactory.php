<?php

namespace Database\Factories;

use App\Models\VisaRequirementTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaRequirementTemplate>
 */
class VisaRequirementTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region' => fake()->randomElement(['Europe', 'Australia', 'Asia']),
            'country_name' => fake()->randomElement(['Germany', 'Australia', 'Japan']),
            'visa_type' => fake()->randomElement(['Tourist visa', 'Student visa']),
            'visa_code' => fake()->optional()->randomElement(['600', '500', 'C', 'D']),
            'requires_institution_name' => false,
            'label' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'source_url' => fake()->url(),
            'source_checked_at' => now()->toDateString(),
            'processing_time_summary' => fake()->sentence(),
            'fee_summary' => fake()->optional()->sentence(3),
            'stay_summary' => fake()->optional()->sentence(3),
            'is_active' => true,
        ];
    }
}
