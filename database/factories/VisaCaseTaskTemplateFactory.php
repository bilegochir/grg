<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\VisaCaseStatus;
use App\Models\Agency;
use App\Models\VisaCaseTaskTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCaseTaskTemplate>
 */
class VisaCaseTaskTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'visa_case_status' => fake()->randomElement(VisaCaseStatus::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_in_days' => fake()->optional()->numberBetween(0, 14),
            'sort_order' => 1,
        ];
    }
}
