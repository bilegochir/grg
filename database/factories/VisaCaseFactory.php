<?php

namespace Database\Factories;

use App\Enums\VisaCasePriority;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VisaCase>
 */
class VisaCaseFactory extends Factory
{
    protected $model = VisaCase::class;

    public function definition(): array
    {
        $visaType = VisaType::factory()->create();
        $stage = VisaWorkflowStage::factory()->create([
            'visa_type_id' => $visaType->id,
            'name' => 'Documents Pending',
            'slug' => 'documents-pending',
            'position' => 1,
            'color' => 'amber',
            'is_default' => true,
        ]);

        return [
            'applicant_id' => Applicant::factory(),
            'visa_type_id' => $visaType->id,
            'target_country_id' => $visaType->target_country_id,
            'branch_id' => Branch::factory(),
            'assigned_to_user_id' => User::factory(),
            'current_stage_id' => $stage->id,
            'priority' => fake()->randomElement(VisaCasePriority::cases()),
            'reference_code' => 'VC-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'expected_submission_at' => now()->addWeek()->toDateString(),
            'expected_decision_at' => now()->addWeeks(6)->toDateString(),
            'closed_at' => null,
        ];
    }
}
