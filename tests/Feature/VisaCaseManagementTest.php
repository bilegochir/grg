<?php

use App\Enums\VisaCasePriority;
use App\Models\Applicant;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use Inertia\Testing\AssertableInertia as Assert;

function visaCaseSetup(): array
{
    $country = TargetCountry::factory()->create([
        'name' => 'Japan',
        'slug' => 'japan',
    ]);

    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
        'name' => 'Tourist',
        'slug' => 'tourist',
    ]);

    $pending = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Documents Pending',
        'slug' => 'documents-pending',
        'position' => 1,
        'color' => 'amber',
        'is_default' => true,
    ]);

    $review = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Under Review',
        'slug' => 'under-review',
        'position' => 2,
        'color' => 'blue',
    ]);

    return [$country, $visaType, $pending, $review];
}

it('creates a visa case with internal and client-visible notes', function () {
    $user = User::factory()->create();
    $applicant = Applicant::factory()->create();
    [$country, $visaType] = visaCaseSetup();

    $response = $this
        ->actingAs($user)
        ->post(route('cases.store'), [
            'applicant_id' => $applicant->id,
            'visa_type_id' => $visaType->id,
            'assigned_to_user_id' => $user->id,
            'priority' => VisaCasePriority::Vip->value,
            'expected_submission_at' => '2026-06-01',
            'expected_decision_at' => '2026-07-01',
            'internal_note' => 'Internal checklist still needs bank statements.',
            'client_note' => 'We have started your case and are reviewing your file.',
        ]);

    $visaCase = VisaCase::query()->first();

    $response->assertRedirect(route('cases.show', $visaCase));

    expect($visaCase->target_country_id)->toBe($country->id);
    expect($visaCase->priority)->toBe(VisaCasePriority::Vip);
    expect($visaCase->notes()->count())->toBe(2);
    expect($visaCase->stageHistories()->count())->toBe(1);
});

it('updates a visa case stage and records history', function () {
    $user = User::factory()->create();
    $applicant = Applicant::factory()->create();
    [, $visaType, $pending, $review] = visaCaseSetup();

    $visaCase = VisaCase::query()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $visaType->target_country_id,
        'assigned_to_user_id' => $user->id,
        'current_stage_id' => $pending->id,
        'priority' => VisaCasePriority::Normal,
        'reference_code' => 'VC-TEST-001',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('cases.stage.update', $visaCase), [
            'stage_id' => $review->id,
        ]);

    $response->assertRedirect();

    $visaCase->refresh();

    expect($visaCase->current_stage_id)->toBe($review->id);
    expect($visaCase->stageHistories()->count())->toBe(1);
    expect($visaCase->activities()->count())->toBe(2);
});

it('includes pathway and setup preview metadata for case creation', function () {
    $user = User::factory()->create();
    grantPermissions($user, ['cases.view']);

    [$country, $visaType, $pending] = visaCaseSetup();

    $visaType->update([
        'name' => 'Student 500',
        'official_subclass' => 'Subclass 500',
        'financial_proof_required' => true,
        'medical_required' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('cases.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Cases/Index')
        ->where('caseMeta.visaTypes.0.pathway', 'Student')
        ->where('caseMeta.visaTypes.0.default_stage_name', $pending->name)
        ->where('caseMeta.visaTypes.0.workflow_stages_count', 2)
    );
});
