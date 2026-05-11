<?php

use App\Enums\VisaCaseDocumentStatus;
use App\Models\Applicant;
use App\Models\DocumentTemplate;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function visaDocumentSetup(): array
{
    $country = TargetCountry::factory()->create([
        'name' => 'South Korea',
        'slug' => 'south-korea',
    ]);

    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
        'name' => 'Student',
        'slug' => 'student',
    ]);

    VisaWorkflowStage::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Documents Pending',
        'slug' => 'documents-pending',
        'position' => 1,
        'color' => 'amber',
        'is_default' => true,
    ]);

    $passportTemplate = DocumentTemplate::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Passport Copy',
        'slug' => 'passport-copy',
        'category' => 'Identity',
        'client_instructions' => 'Upload the full bio page in colour.',
        'accepted_file_types' => ['pdf'],
        'max_file_size_mb' => 5,
        'due_days' => 2,
        'position' => 1,
        'tracks_expiry' => true,
    ]);

    DocumentTemplate::factory()->create([
        'visa_type_id' => $visaType->id,
        'name' => 'Bank Statement',
        'slug' => 'bank-statement',
        'position' => 2,
    ]);

    return [$country, $visaType, $passportTemplate];
}

it('creates case checklist items from document templates', function () {
    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['cases.create']);
    $applicant = Applicant::factory()->create();
    [, $visaType] = visaDocumentSetup();

    $this->actingAs($user)->post(route('cases.store'), [
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'priority' => 'normal',
    ]);

    $visaCase = VisaCase::query()->first();

    expect($visaCase->documents()->count())->toBe(2);
    expect($visaCase->documents()->pluck('name')->all())->toContain('Passport Copy', 'Bank Statement');
    $passportDocument = $visaCase->documents()->where('name', 'Passport Copy')->firstOrFail();
    expect($passportDocument->category)->toBe('Identity');
    expect($passportDocument->accepted_file_types)->toBe(['pdf']);
    expect($passportDocument->max_file_size_mb)->toBe(5);
    expect($passportDocument->due_days)->toBe(2);
});

it('uploads document versions and updates verification state', function () {
    Storage::fake('local');

    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['cases.create', 'cases.update', 'documents.review']);
    $applicant = Applicant::factory()->create();
    [, $visaType, $passportTemplate] = visaDocumentSetup();

    $this->actingAs($user)->post(route('cases.store'), [
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'priority' => 'normal',
    ]);

    $visaCase = VisaCase::query()->first();
    $document = $visaCase->documents()->where('document_template_id', $passportTemplate->id)->firstOrFail();

    $this->actingAs($user)->post(
        route('cases.documents.upload', ['case' => $visaCase, 'document' => $document]),
        ['file' => UploadedFile::fake()->create('passport.pdf', 120, 'application/pdf')],
    );

    $document->refresh();

    expect($document->status)->toBe(VisaCaseDocumentStatus::Uploaded);
    expect($document->versions()->count())->toBe(1);

    $this->actingAs($user)->patch(
        route('cases.documents.status.update', ['case' => $visaCase, 'document' => $document]),
        [
            'status' => 'verified',
            'expiry_date' => '2030-01-01',
        ],
    );

    $document->refresh();

    expect($document->status)->toBe(VisaCaseDocumentStatus::Verified);
    expect($document->expiry_date?->toDateString())->toBe('2030-01-01');
});

it('validates uploads against template-driven file rules', function () {
    Storage::fake('local');

    $user = grantPermissions(User::factory()->create(['branch_id' => null]), ['cases.create', 'cases.update']);
    $applicant = Applicant::factory()->create();
    [, $visaType] = visaDocumentSetup();

    $this->actingAs($user)->post(route('cases.store'), [
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'priority' => 'normal',
    ]);

    $visaCase = VisaCase::query()->first();
    $document = $visaCase->documents()->where('name', 'Passport Copy')->firstOrFail();

    $response = $this->actingAs($user)->post(
        route('cases.documents.upload', ['case' => $visaCase, 'document' => $document]),
        ['file' => UploadedFile::fake()->create('passport.png', 120, 'image/png')],
    );

    $response->assertSessionHasErrors('file');
    expect($document->fresh()->versions()->count())->toBe(0);
});
