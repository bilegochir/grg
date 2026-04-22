<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Agency;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'client_id' => null,
            'visa_case_id' => null,
            'visa_case_task_template_id' => null,
            'assigned_user_id' => null,
            'created_by_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_at' => fake()->optional()->dateTimeBetween('-1 day', '+7 days'),
            'completed_at' => null,
        ];
    }
}
