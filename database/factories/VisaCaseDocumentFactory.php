<?php

namespace Database\Factories;

use App\Enums\VisaCaseDocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCaseDocument>
 */
class VisaCaseDocumentFactory extends Factory
{
    protected $model = VisaCaseDocument::class;

    public function definition(): array
    {
        return [
            'visa_case_id' => VisaCase::factory(),
            'document_template_id' => DocumentTemplate::factory(),
            'name' => 'Passport Copy',
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Identity', 'Finance']),
            'client_instructions' => fake()->sentence(10),
            'agent_guidance' => fake()->sentence(12),
            'sample_hint' => 'PDF preferred',
            'accepted_file_types' => ['pdf', 'jpg'],
            'max_files' => 1,
            'max_file_size_mb' => 20,
            'due_days' => 7,
            'is_repeatable' => false,
            'position' => 1,
            'status' => VisaCaseDocumentStatus::Pending,
            'is_required' => true,
            'tracks_expiry' => true,
            'expiry_date' => null,
            'verified_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'latest_version_id' => null,
        ];
    }
}
