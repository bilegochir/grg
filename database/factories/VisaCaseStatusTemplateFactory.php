<?php

namespace Database\Factories;

use App\Enums\VisaCaseStatus;
use App\Models\Agency;
use App\Models\VisaCaseStatusTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCaseStatusTemplate>
 */
class VisaCaseStatusTemplateFactory extends Factory
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
                static fn (VisaCaseStatus $status): string => $status->value,
                VisaCaseStatus::cases(),
            )),
            'label' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 7),
        ];
    }
}
