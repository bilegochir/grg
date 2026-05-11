<?php

use App\Models\Applicant;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

it('creates ad hoc tasks for a case', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['cases.view', 'cases.update']);
    $applicant = Applicant::factory()->create();

    $country = TargetCountry::factory()->create([
        'name' => 'Australia',
        'slug' => 'australia',
    ]);

    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
        'name' => 'Visitor',
        'slug' => 'visitor',
    ]);

    $stage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Documents Pending',
        'slug' => 'documents-pending',
        'position' => 1,
        'color' => 'amber',
        'is_default' => true,
    ]);

    $visaCase = VisaCase::query()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'assigned_to_user_id' => $user->id,
        'current_stage_id' => $stage->id,
        'priority' => 'normal',
        'reference_code' => 'VC-TASK-001',
    ]);

    $response = $this->actingAs($user)->post(route('cases.tasks.store', $visaCase), [
        'title' => 'Request updated bank statement',
        'description' => 'Ask the applicant for the latest 3-month statement.',
        'due_at' => '2026-06-10',
        'stage_id' => $stage->id,
    ]);

    $response->assertRedirect();

    $task = $visaCase->fresh()->tasks()->first();

    expect($task)->not->toBeNull();
    expect($task->name)->toBe('Request updated bank statement');
    expect($task->description)->toBe('Ask the applicant for the latest 3-month statement.');
    expect($task->due_at?->toDateString())->toBe('2026-06-10');
    expect($task->status)->toBe('pending');
});
