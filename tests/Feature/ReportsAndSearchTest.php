<?php

use App\Enums\LeadStatus;
use App\Enums\VisaCaseDocumentStatus;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseTask;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

it('renders reports with operational metrics and alerts', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['dashboard.view']);
    $country = TargetCountry::factory()->create(['name' => 'Australia']);
    $visaType = VisaType::factory()->create(['target_country_id' => $country->id]);
    $stage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Under Review',
        'slug' => 'under-review',
        'is_default' => true,
    ]);
    $applicant = Applicant::factory()->create();
    $visaCase = VisaCase::factory()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'current_stage_id' => $stage->id,
        'assigned_to_user_id' => $user->id,
        'updated_at' => now()->subDays(6),
    ]);

    Lead::factory()->create(['status' => LeadStatus::New]);
    Lead::factory()->create(['status' => LeadStatus::Qualified, 'updated_at' => now()->subDays(4)]);

    VisaCaseTask::query()->create([
        'visa_case_id' => $visaCase->id,
        'name' => 'Follow up with applicant',
        'assigned_to_user_id' => $user->id,
        'status' => 'pending',
        'due_at' => now()->subDay(),
        'position' => 1,
    ]);

    VisaCaseDocument::factory()->create([
        'visa_case_id' => $visaCase->id,
        'status' => VisaCaseDocumentStatus::Uploaded,
        'updated_at' => now()->subDays(3),
    ]);

    Invoice::query()->create([
        'visa_case_id' => $visaCase->id,
        'applicant_id' => $applicant->id,
        'number' => 'INV-2026-0001',
        'status' => 'issued',
        'currency' => 'USD',
        'line_items' => [['label' => 'Service fee', 'amount' => 250]],
        'subtotal' => 250,
        'total' => 250,
        'balance_due' => 250,
        'issued_at' => now()->toDateString(),
        'due_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reports/Index')
        ->where('leadFunnel.0.label', 'New')
        ->has('alerts', 5)
        ->where('finance.overdue_invoices', 1));
});

it('orders country demand by open case count and ignores closed cases', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['dashboard.view']);

    $australia = TargetCountry::factory()->create(['name' => 'Australia']);
    $canada = TargetCountry::factory()->create(['name' => 'Canada']);

    $australiaVisaType = VisaType::factory()->create(['target_country_id' => $australia->id]);
    $canadaVisaType = VisaType::factory()->create(['target_country_id' => $canada->id]);

    $australiaStage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $australiaVisaType->id,
        'name' => 'Documents Pending',
        'slug' => 'documents-pending',
        'is_default' => true,
    ]);

    $canadaStage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $canadaVisaType->id,
        'name' => 'Under Review',
        'slug' => 'under-review',
        'is_default' => true,
    ]);

    $firstApplicant = Applicant::factory()->create();
    $secondApplicant = Applicant::factory()->create();
    $thirdApplicant = Applicant::factory()->create();

    VisaCase::factory()->create([
        'applicant_id' => $firstApplicant->id,
        'visa_type_id' => $australiaVisaType->id,
        'target_country_id' => $australia->id,
        'current_stage_id' => $australiaStage->id,
    ]);

    VisaCase::factory()->create([
        'applicant_id' => $secondApplicant->id,
        'visa_type_id' => $australiaVisaType->id,
        'target_country_id' => $australia->id,
        'current_stage_id' => $australiaStage->id,
    ]);

    VisaCase::factory()->create([
        'applicant_id' => $thirdApplicant->id,
        'visa_type_id' => $canadaVisaType->id,
        'target_country_id' => $canada->id,
        'current_stage_id' => $canadaStage->id,
        'closed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reports/Index')
        ->has('countryDemand', 1)
        ->where('countryDemand.0.label', 'Australia')
        ->where('countryDemand.0.value', 2));
});

it('returns grouped global search results', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['dashboard.view']);
    $applicant = Applicant::factory()->create([
        'first_name' => 'Nara',
        'last_name' => 'Kim',
        'email' => 'nara@example.com',
    ]);

    $lead = Lead::factory()->create([
        'first_name' => 'Nara',
        'last_name' => 'Cho',
        'email' => 'lead@example.com',
    ]);

    $response = $this->actingAs($user)->getJson(route('search.global', ['q' => 'Nara']));

    $response->assertOk();
    $response->assertJsonCount(2, 'results');
    $response->assertJsonFragment([
        'type' => 'Lead',
        'title' => $lead->full_name,
    ]);
    $response->assertJsonFragment([
        'type' => 'Applicant',
        'title' => $applicant->full_name,
    ]);
});
