<?php

namespace Database\Factories;

use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VisaWorkflowStage>
 */
class VisaWorkflowStageFactory extends Factory
{
    protected $model = VisaWorkflowStage::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Documents Pending',
            'Under Review',
            'Submitted to Embassy',
            'Decision',
            'Closed',
        ]);

        return [
            'visa_type_id' => VisaType::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'position' => 1,
            'color' => fake()->randomElement(['amber', 'blue', 'violet', 'emerald', 'slate']),
            'is_default' => false,
            'is_closed' => false,
        ];
    }
}
