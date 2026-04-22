<?php

namespace Database\Factories;

use App\Enums\VisaRequirementStatus;
use App\Models\VisaCaseRequirement;
use App\Models\VisaCase;
use App\Models\VisaRequirementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCaseRequirement>
 */
class VisaCaseRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visa_case_id' => VisaCase::factory(),
            'visa_requirement_item_id' => VisaRequirementItem::factory(),
            'category' => fake()->randomElement(['Identity', 'Financial', 'Travel', 'Sponsor']),
            'label' => fake()->sentence(4),
            'help_text' => fake()->optional()->sentence(),
            'is_required' => true,
            'status' => VisaRequirementStatus::Pending,
            'due_at' => fake()->optional()->date(),
            'requested_at' => null,
            'received_at' => null,
            'reviewed_at' => null,
            'review_notes' => fake()->optional()->sentence(),
            'is_completed' => false,
            'completed_at' => null,
            'sort_order' => fake()->numberBetween(1, 12),
        ];
    }
}
