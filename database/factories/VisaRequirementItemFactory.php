<?php

namespace Database\Factories;

use App\Models\VisaRequirementItem;
use App\Models\VisaRequirementTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaRequirementItem>
 */
class VisaRequirementItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visa_requirement_template_id' => VisaRequirementTemplate::factory(),
            'category' => fake()->randomElement(['Identity', 'Financial', 'Travel', 'Sponsor']),
            'label' => fake()->sentence(4),
            'help_text' => fake()->optional()->sentence(),
            'is_required' => true,
            'sort_order' => fake()->numberBetween(1, 12),
        ];
    }
}
