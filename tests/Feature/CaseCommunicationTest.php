<?php

use App\Enums\ApplicantNotificationEvent;
use App\Models\Applicant;
use App\Models\CommunicationTemplate;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use App\Notifications\CaseCommunicationNotification;
use Illuminate\Support\Facades\Notification;

function communicationCaseSetup(): array
{
    $country = TargetCountry::factory()->create([
        'name' => 'United Kingdom',
        'slug' => 'united-kingdom',
    ]);

    $visaType = VisaType::factory()->create([
        'target_country_id' => $country->id,
        'name' => 'Work',
        'slug' => 'work',
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

it('sends an applicant notification when a case stage changes', function () {
    Notification::fake();

    $user = User::factory()->create();
    $applicant = Applicant::factory()->create();
    [$country, $visaType, $pending, $review] = communicationCaseSetup();

    CommunicationTemplate::factory()->create([
        'name' => 'Case status email',
        'key' => 'case-status-change',
        'channel' => 'email',
        'locale' => 'en',
        'subject' => 'Case {{case_reference}} is now {{stage_name}}',
        'body' => 'Hello {{applicant_name}}, your case moved to {{stage_name}}.',
    ]);

    $visaCase = VisaCase::query()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'assigned_to_user_id' => $user->id,
        'current_stage_id' => $pending->id,
        'priority' => 'normal',
        'reference_code' => 'VC-COMM-001',
    ]);

    $this
        ->actingAs($user)
        ->patch(route('cases.stage.update', $visaCase), [
            'stage_id' => $review->id,
        ])
        ->assertRedirect();

    Notification::assertSentTo($applicant, CaseCommunicationNotification::class);

    expect($visaCase->fresh()->messages()->count())->toBeGreaterThan(0);
});

it('stores an outbound case communication and notifies the applicant', function () {
    Notification::fake();

    $user = User::factory()->create();
    $applicant = Applicant::factory()->create();
    [$country, $visaType, $pending] = communicationCaseSetup();

    $visaCase = VisaCase::query()->create([
        'applicant_id' => $applicant->id,
        'visa_type_id' => $visaType->id,
        'target_country_id' => $country->id,
        'assigned_to_user_id' => $user->id,
        'current_stage_id' => $pending->id,
        'priority' => 'normal',
        'reference_code' => 'VC-COMM-002',
    ]);

    $this
        ->actingAs($user)
        ->post(route('cases.messages.store', $visaCase), [
            'direction' => 'outbound',
            'sender_type' => 'staff',
            'channel' => 'email',
            'notification_event' => ApplicantNotificationEvent::DocumentRequests->value,
            'send_notification' => true,
            'subject' => 'Please upload your missing file',
            'body' => 'Please upload a clearer passport scan.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($applicant, CaseCommunicationNotification::class);

    $message = $visaCase->fresh()->messages()->first();

    expect($message->channel)->toBe('email');
    expect($message->notification_event)->toBe(ApplicantNotificationEvent::DocumentRequests->value);
});
