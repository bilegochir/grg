<?php

use App\Models\Applicant;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaTaskTemplate;
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

it('automatically creates only shared and current-stage workflow tasks when a case is created', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['cases.create', 'cases.view', 'cases.update', 'cases.assign']);
    $applicant = Applicant::factory()->create();
    $country = TargetCountry::factory()->create([
        'name' => 'Canada',
        'slug' => 'canada',
    ]);

    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
        'name' => 'Study Permit',
        'slug' => 'study-permit',
    ]);

    $intakeStage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Intake',
        'slug' => 'intake',
        'position' => 1,
        'is_default' => true,
    ]);

    $reviewStage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Review',
        'slug' => 'review',
        'position' => 2,
    ]);

    VisaTaskTemplate::query()->create([
        'visa_type_id' => $visaType->id,
        'visa_workflow_stage_id' => null,
        'name' => 'Confirm lead details',
        'slug' => 'confirm-lead-details',
        'position' => 1,
        'due_days' => 1,
        'is_required' => true,
        'is_client_visible' => false,
    ]);

    VisaTaskTemplate::query()->create([
        'visa_type_id' => $visaType->id,
        'visa_workflow_stage_id' => $intakeStage->id,
        'name' => 'Collect intake documents',
        'slug' => 'collect-intake-documents',
        'position' => 1,
        'due_days' => 3,
        'is_required' => true,
        'is_client_visible' => true,
    ]);

    VisaTaskTemplate::query()->create([
        'visa_type_id' => $visaType->id,
        'visa_workflow_stage_id' => $reviewStage->id,
        'name' => 'Run eligibility review',
        'slug' => 'run-eligibility-review',
        'position' => 1,
        'due_days' => 5,
        'is_required' => true,
        'is_client_visible' => false,
    ]);

    $this->actingAs($user)->post(route('cases.store'), [
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'assigned_to_user_id' => $user->id,
        'priority' => 'normal',
    ])->assertRedirect();

    $visaCase = VisaCase::query()->firstOrFail();
    $tasks = $visaCase->tasks()->orderBy('position')->get();

    expect($tasks)->toHaveCount(2);
    expect($tasks->pluck('name')->all())->toBe([
        'Confirm lead details',
        'Collect intake documents',
    ]);
    expect($tasks->pluck('position')->all())->toBe([1, 2]);
});

it('automatically instantiates stage tasks when a case moves forward without duplicating them', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['cases.create', 'cases.view', 'cases.update', 'cases.assign']);
    $applicant = Applicant::factory()->create();
    $country = TargetCountry::factory()->create();
    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
    ]);

    $pendingStage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Documents Pending',
        'slug' => 'documents-pending',
        'position' => 1,
        'is_default' => true,
    ]);

    $reviewStage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Under Review',
        'slug' => 'under-review',
        'position' => 2,
    ]);

    VisaTaskTemplate::query()->create([
        'visa_type_id' => $visaType->id,
        'visa_workflow_stage_id' => $pendingStage->id,
        'name' => 'Gather passport copy',
        'slug' => 'gather-passport-copy',
        'position' => 1,
        'is_required' => true,
        'is_client_visible' => true,
    ]);

    $reviewTemplate = VisaTaskTemplate::query()->create([
        'visa_type_id' => $visaType->id,
        'visa_workflow_stage_id' => $reviewStage->id,
        'name' => 'Complete internal review',
        'slug' => 'complete-internal-review',
        'position' => 1,
        'is_required' => true,
        'is_client_visible' => false,
    ]);

    $this->actingAs($user)->post(route('cases.store'), [
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'assigned_to_user_id' => $user->id,
        'priority' => 'normal',
    ])->assertRedirect();

    $visaCase = VisaCase::query()->firstOrFail();

    expect($visaCase->tasks()->pluck('name')->all())->toBe(['Gather passport copy']);

    $this->actingAs($user)->patch(route('cases.stage.update', $visaCase), [
        'stage_id' => $reviewStage->id,
    ])->assertRedirect();

    $visaCase->refresh();

    expect($visaCase->tasks()->pluck('name')->all())->toBe([
        'Gather passport copy',
        'Complete internal review',
    ]);
    expect($visaCase->tasks()->where('visa_task_template_id', $reviewTemplate->id)->count())->toBe(1);

    $this->actingAs($user)->patch(route('cases.stage.update', $visaCase), [
        'stage_id' => $reviewStage->id,
    ])->assertRedirect();

    expect($visaCase->fresh()->tasks()->where('visa_task_template_id', $reviewTemplate->id)->count())->toBe(1);
});
