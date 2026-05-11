<?php

use App\Enums\VisaCaseDocumentStatus;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseAppointment;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseMessage;
use App\Models\VisaCaseTask;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

it('renders the applicant portal with progress, tasks, documents, and billing data', function () {
    $country = TargetCountry::factory()->create(['name' => 'Japan']);
    $visaType = VisaType::factory()->create([
        'name' => 'Tourist',
        'target_country_id' => $country->id,
    ]);

    $documentsPending = VisaWorkflowStage::factory()->create([
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

    $applicant = Applicant::factory()->create();
    $agent = User::factory()->create();

    $visaCase = VisaCase::factory()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'assigned_to_user_id' => $agent->id,
        'current_stage_id' => $documentsPending->id,
        'reference_code' => 'VC-PORTAL-1',
    ]);

    VisaCaseTask::query()->create([
        'visa_case_id' => $visaCase->id,
        'visa_workflow_stage_id' => $documentsPending->id,
        'assigned_to_user_id' => $agent->id,
        'name' => 'Upload passport copy',
        'description' => 'Please send a clear full-color passport scan.',
        'status' => 'pending',
        'position' => 1,
        'is_required' => true,
        'is_client_visible' => true,
    ]);

    VisaCaseDocument::factory()->create([
        'visa_case_id' => $visaCase->id,
        'name' => 'Passport copy',
        'status' => VisaCaseDocumentStatus::Pending,
    ]);

    VisaCaseAppointment::query()->create([
        'visa_case_id' => $visaCase->id,
        'applicant_id' => $applicant->id,
        'assigned_to_user_id' => $agent->id,
        'title' => 'Review call',
        'appointment_type' => 'consultation',
        'status' => 'scheduled',
        'starts_at' => now()->addDay(),
    ]);

    $invoice = Invoice::query()->create([
        'visa_case_id' => $visaCase->id,
        'applicant_id' => $applicant->id,
        'created_by_user_id' => $agent->id,
        'number' => 'INV-1001',
        'status' => 'partially_paid',
        'currency' => 'USD',
        'line_items' => [
            ['description' => 'Visa service fee', 'amount' => 500],
        ],
        'subtotal' => 500,
        'total' => 500,
        'balance_due' => 200,
        'issued_at' => now()->subDay(),
        'due_at' => now()->addWeek(),
        'client_message' => 'Please settle the remaining balance before submission.',
    ]);

    InvoicePayment::query()->create([
        'invoice_id' => $invoice->id,
        'recorded_by_user_id' => $agent->id,
        'amount' => 300,
        'method' => 'bank_transfer',
        'paid_at' => now()->subHours(12),
    ]);

    VisaCaseMessage::factory()->create([
        'visa_case_id' => $visaCase->id,
        'direction' => 'outbound',
        'sender_type' => 'staff',
        'body' => 'We reviewed your intake and need your passport copy next.',
    ]);

    $response = $this
        ->withSession(['portal_applicant_id' => $applicant->id])
        ->get(route('portal.cases.show', $visaCase));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Case')
        ->where('case.reference_code', 'VC-PORTAL-1')
        ->where('case.workflow.0.name', 'Documents Pending')
        ->where('case.workflow.0.state', 'current')
        ->where('case.summary.documents_waiting_count', 1)
        ->where('case.tasks.0.name', 'Upload passport copy')
        ->where('case.documents.0.name', 'Passport copy')
        ->where('case.invoices.0.number', 'INV-1001')
        ->where('case.invoices.0.paid_amount', '300.00')
        ->has('case.timeline'));
});
