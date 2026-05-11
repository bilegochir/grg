<?php

use App\Models\BusinessSetting;
use App\Models\CommunicationTemplate;
use App\Models\DocumentTemplate;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('renders the settings workspace', function () {
    $user = grantPermissions(User::factory()->create(), ['settings.manage']);
    $country = TargetCountry::factory()->create(['name' => 'Australia', 'slug' => 'australia']);
    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
        'name' => 'Student',
        'slug' => 'student',
    ]);

    DocumentTemplate::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Passport Copy',
        'slug' => 'passport-copy',
    ]);

    CommunicationTemplate::factory()->create([
        'name' => 'Lead follow-up',
        'key' => 'lead-follow-up',
        'channel' => 'email',
        'locale' => 'en',
    ]);

    $response = $this->actingAs($user)->get(route('settings.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Index')
        ->has('countries', 1)
        ->has('visaTypes', 1)
        ->has('documentTemplates', 1)
        ->has('communicationTemplates', 1));
});

it('updates business settings including logo upload', function () {
    Storage::fake('public');

    $user = grantPermissions(User::factory()->create(), ['settings.manage']);
    BusinessSetting::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('settings.business.update'), [
            'business_name' => 'North Star Visa',
            'contact_email' => 'team@northstar.test',
            'contact_phone' => '+976 7711 2233',
            'contact_address' => 'Peace Avenue 12',
            'default_locale' => 'mn',
            'sms_provider' => 'log',
            'sms_sender' => 'NorthStar',
            'multi_branch_enabled' => true,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

    $response->assertRedirect();

    $settings = BusinessSetting::current()->refresh();

    expect($settings->business_name)->toBe('North Star Visa');
    expect($settings->default_locale)->toBe('mn');
    expect($settings->sms_provider)->toBe('log');
    expect($settings->multi_branch_enabled)->toBeTrue();
    expect($settings->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($settings->logo_path);
});

it('creates a visa type and provisions default workflow stages', function () {
    $user = grantPermissions(User::factory()->create(), ['settings.manage']);
    $country = TargetCountry::factory()->create(['name' => 'Japan', 'slug' => 'japan']);

    $response = $this
        ->actingAs($user)
        ->post(route('settings.visa-types.store'), [
            'target_country_id' => $country->id,
            'name' => 'Work',
            'code' => 'JP-WORK',
            'slug' => 'work',
            'is_active' => true,
            'submission_sla_days' => 14,
            'decision_sla_days' => 45,
            'validity_months' => 12,
            'stay_duration_days' => 90,
            'entry_type' => 'multiple_entry',
            'service_scope' => 'new_application',
            'priority_support' => true,
            'dependants_allowed' => true,
            'biometrics_required' => true,
            'interview_required' => false,
            'medical_required' => true,
            'police_clearance_required' => true,
            'financial_proof_required' => true,
            'checklist_intro' => 'Prepare civil and financial documents first.',
            'portal_guidance' => 'We will review your uploads before submission.',
            'notes' => 'Watch embassy holiday windows.',
        ]);

    $response->assertRedirect();

    $visaType = VisaType::query()->where('slug', 'work')->firstOrFail();

    expect($visaType->workflowStages()->count())->toBe(5);
    expect($visaType->documentTemplates()->count())->toBe(0);
    expect($visaType->code)->toBe('JP-WORK');
    expect($visaType->submission_sla_days)->toBe(14);
    expect($visaType->decision_sla_days)->toBe(45);
    expect($visaType->entry_type)->toBe('multiple_entry');
    expect($visaType->financial_proof_required)->toBeTrue();
});

it('updates advanced visa type settings', function () {
    $user = grantPermissions(User::factory()->create(), ['settings.manage']);
    $country = TargetCountry::factory()->create(['name' => 'Australia', 'slug' => 'australia']);
    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
        'name' => 'Student',
        'slug' => 'student',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('settings.visa-types.update', $visaType), [
            'target_country_id' => $country->id,
            'name' => 'Student Visa',
            'code' => 'AU-STU',
            'slug' => 'student-visa',
            'is_active' => true,
            'submission_sla_days' => 10,
            'decision_sla_days' => 30,
            'validity_months' => 24,
            'stay_duration_days' => 180,
            'entry_type' => 'single_entry',
            'service_scope' => 'renewal',
            'priority_support' => false,
            'dependants_allowed' => true,
            'biometrics_required' => true,
            'interview_required' => true,
            'medical_required' => true,
            'police_clearance_required' => false,
            'financial_proof_required' => true,
            'checklist_intro' => 'Gather school and bank documents early.',
            'portal_guidance' => 'Uploads are checked within two business days.',
            'notes' => 'Student stream varies by intake season.',
        ]);

    $response->assertRedirect();

    $visaType->refresh();

    expect($visaType->name)->toBe('Student Visa');
    expect($visaType->code)->toBe('AU-STU');
    expect($visaType->slug)->toBe('student-visa');
    expect($visaType->submission_sla_days)->toBe(10);
    expect($visaType->decision_sla_days)->toBe(30);
    expect($visaType->validity_months)->toBe(24);
    expect($visaType->stay_duration_days)->toBe(180);
    expect($visaType->entry_type)->toBe('single_entry');
    expect($visaType->service_scope)->toBe('renewal');
    expect($visaType->priority_support)->toBeFalse();
    expect($visaType->dependants_allowed)->toBeTrue();
    expect($visaType->biometrics_required)->toBeTrue();
    expect($visaType->interview_required)->toBeTrue();
    expect($visaType->medical_required)->toBeTrue();
    expect($visaType->financial_proof_required)->toBeTrue();
    expect($visaType->checklist_intro)->toBe('Gather school and bank documents early.');
    expect($visaType->portal_guidance)->toBe('Uploads are checked within two business days.');
});

it('creates and updates a document checklist template', function () {
    $user = grantPermissions(User::factory()->create(), ['settings.manage']);
    $visaType = VisaType::factory()->create();

    $createResponse = $this
        ->actingAs($user)
        ->post(route('settings.document-templates.store'), [
            'visa_type_id' => $visaType->id,
            'name' => 'Police Clearance',
            'slug' => 'police-clearance',
            'description' => 'Upload the latest certificate.',
            'category' => 'Compliance',
            'client_instructions' => 'Upload the latest certificate from the issuing authority.',
            'agent_guidance' => 'Check issue date and translation status.',
            'sample_hint' => 'PDF is preferred.',
            'accepted_file_types' => ['pdf', 'jpg'],
            'max_files' => 2,
            'max_file_size_mb' => 12,
            'due_days' => 7,
            'is_repeatable' => true,
            'position' => 2,
            'is_required' => true,
            'tracks_expiry' => true,
        ]);

    $createResponse->assertRedirect();

    $template = DocumentTemplate::query()->where('slug', 'police-clearance')->firstOrFail();

    $updateResponse = $this
        ->actingAs($user)
        ->patch(route('settings.document-templates.update', $template), [
            'visa_type_id' => $visaType->id,
            'name' => 'Police certificate',
            'slug' => 'police-certificate',
            'description' => 'Issued within the accepted validity period.',
            'category' => 'Identity',
            'client_instructions' => 'Upload the newest version available.',
            'agent_guidance' => 'Reject if older than the embassy window.',
            'sample_hint' => 'Make sure the stamp is clearly visible.',
            'accepted_file_types' => ['pdf'],
            'max_files' => 1,
            'max_file_size_mb' => 8,
            'due_days' => 10,
            'is_repeatable' => false,
            'position' => 3,
            'is_required' => false,
            'tracks_expiry' => true,
        ]);

    $updateResponse->assertRedirect();

    $template->refresh();

    expect($template->name)->toBe('Police certificate');
    expect($template->category)->toBe('Identity');
    expect($template->accepted_file_types)->toBe(['pdf']);
    expect($template->max_file_size_mb)->toBe(8);
    expect($template->due_days)->toBe(10);
    expect($template->position)->toBe(3);
    expect($template->is_required)->toBeFalse();
});

it('prevents deleting visa types that already have cases', function () {
    $user = grantPermissions(User::factory()->create(), ['settings.manage']);
    $country = TargetCountry::factory()->create();
    $visaType = VisaType::factory()->create(['target_country_id' => $country->id]);
    $stage = VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'is_default' => true,
    ]);

    VisaCase::factory()->create([
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'current_stage_id' => $stage->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('settings.visa-types.destroy', $visaType));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect(VisaType::query()->whereKey($visaType->id)->exists())->toBeTrue();
});
