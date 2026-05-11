<?php

use App\Enums\LeadStatus;
use App\Enums\VisaCaseDocumentStatus;
use App\Models\Applicant;
use App\Models\Lead;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

it('renders dashboard with live metrics', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['dashboard.view']);

    Lead::factory()->create(['status' => LeadStatus::New]);
    Lead::factory()->create(['status' => LeadStatus::Qualified]);

    $country = TargetCountry::factory()->create();
    $visaType = VisaType::factory()->create(['target_country_id' => $country->id]);
    $stage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'slug' => 'under-review',
        'name' => 'Under Review',
        'is_default' => true,
    ]);

    $applicant = Applicant::factory()->create([
        'lead_id' => Lead::factory()->create(['status' => LeadStatus::Qualified])->id,
    ]);

    $visaCase = VisaCase::factory()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'current_stage_id' => $stage->id,
    ]);

    VisaCaseDocument::factory()->create([
        'visa_case_id' => $visaCase->id,
        'status' => VisaCaseDocumentStatus::Pending,
    ]);

    $visaCase->activities()->create([
        'event' => 'visa_case.created',
        'description' => 'Visa case created from test.',
        'caused_by_user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('stats.0.value', 1)
        ->where('stats.2.value', 1)
        ->where('attentionItems.0.count', 1)
        ->has('activity', 1)
        ->where('activity.0.description', 'Visa case created from test.')
        ->where('hasData', true));
});
