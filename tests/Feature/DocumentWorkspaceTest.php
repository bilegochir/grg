<?php

use App\Enums\VisaCaseDocumentStatus;
use App\Models\Applicant;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

it('renders the documents workspace with live document rows', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['documents.review']);
    $country = TargetCountry::factory()->create(['name' => 'Japan', 'slug' => 'japan']);
    $visaType = VisaType::factory()->create(['target_country_id' => $country->id]);
    $stage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Under Review',
        'slug' => 'under-review',
        'is_default' => true,
    ]);
    $applicant = Applicant::factory()->create([
        'first_name' => 'Amina',
        'last_name' => 'Sato',
    ]);
    $visaCase = VisaCase::factory()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'current_stage_id' => $stage->id,
        'assigned_to_user_id' => $user->id,
    ]);

    VisaCaseDocument::factory()->create([
        'visa_case_id' => $visaCase->id,
        'name' => 'Passport Copy',
        'status' => VisaCaseDocumentStatus::Uploaded,
    ]);

    $response = $this->actingAs($user)->get(route('documents.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Documents/Index')
        ->where('summary.total', 1)
        ->where('summary.uploaded', 1)
        ->where('documents.data.0.name', 'Passport Copy')
        ->where('documents.data.0.case.applicant_name', 'Amina Sato'));
});

it('filters the documents workspace by status', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['documents.review']);
    $country = TargetCountry::factory()->create();
    $visaType = VisaType::factory()->create(['target_country_id' => $country->id]);
    $stage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'is_default' => true,
    ]);
    $applicant = Applicant::factory()->create();
    $visaCase = VisaCase::factory()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'current_stage_id' => $stage->id,
    ]);

    VisaCaseDocument::factory()->create([
        'visa_case_id' => $visaCase->id,
        'name' => 'Financial Proof',
        'status' => VisaCaseDocumentStatus::Pending,
    ]);

    VisaCaseDocument::factory()->create([
        'visa_case_id' => $visaCase->id,
        'name' => 'Police Clearance',
        'status' => VisaCaseDocumentStatus::Verified,
    ]);

    $response = $this->actingAs($user)->get(route('documents.index', ['status' => 'verified']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Documents/Index')
        ->where('filters.status', 'verified')
        ->has('documents.data', 1)
        ->where('documents.data.0.name', 'Police Clearance'));
});
