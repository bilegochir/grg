<?php

namespace Database\Factories;

use App\Models\DocumentTemplate;
use App\Models\VisaType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentTemplate>
 */
class DocumentTemplateFactory extends Factory
{
    protected $model = DocumentTemplate::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Passport Copy',
            'Bank Statement',
            'Police Clearance',
            'Medical Report',
        ]);

        return [
            'visa_type_id' => VisaType::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Identity', 'Finance', 'Compliance']),
            'client_instructions' => fake()->sentence(10),
            'agent_guidance' => fake()->sentence(12),
            'sample_hint' => fake()->randomElement(['PDF preferred', 'Colour scan required', 'Signed copy only']),
            'accepted_file_types' => ['pdf', 'jpg'],
            'max_files' => 1,
            'max_file_size_mb' => 20,
            'due_days' => fake()->randomElement([3, 5, 7, 14, null]),
            'is_repeatable' => false,
            'position' => 1,
            'is_required' => true,
            'tracks_expiry' => false,
        ];
    }
}
