<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Agency;
use App\Models\TaskStatusTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskStatusTemplate>
 */
class TaskStatusTemplateFactory extends Factory
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
            'status_key' => fake()->randomElement(array_map(
                static fn (TaskStatus $status): string => $status->value,
                TaskStatus::cases(),
            )),
            'label' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 3),
        ];
    }
}
