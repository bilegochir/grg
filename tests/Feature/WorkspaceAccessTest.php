<?php

use App\Models\Applicant;
use App\Models\Branch;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

it('forbids access when the user lacks the required permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertForbidden();
});

it('scopes case lists to the signed in users branch', function () {
    $branchA = Branch::factory()->create(['name' => 'Central']);
    $branchB = Branch::factory()->create(['name' => 'Airport']);

    $user = grantPermissions(User::factory()->create(['branch_id' => $branchA->id]), ['cases.view']);

    $country = TargetCountry::factory()->create();
    $visaType = VisaType::factory()->create(['target_country_id' => $country->id]);
    $stage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'is_default' => true,
    ]);

    $applicantA = Applicant::factory()->create(['first_name' => 'Sara', 'last_name' => 'Bold']);
    $applicantB = Applicant::factory()->create(['first_name' => 'Tomo', 'last_name' => 'Imai']);

    VisaCase::factory()->create([
        'applicant_id' => $applicantA->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'branch_id' => $branchA->id,
        'current_stage_id' => $stage->id,
    ]);

    VisaCase::factory()->create([
        'applicant_id' => $applicantB->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'branch_id' => $branchB->id,
        'current_stage_id' => $stage->id,
    ]);

    $response = $this->actingAs($user)->get(route('cases.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Cases/Index')
        ->has('cases.data', 1)
        ->where('cases.data.0.applicant_name', 'Sara Bold'));
});
