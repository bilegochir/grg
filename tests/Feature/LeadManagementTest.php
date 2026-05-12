<?php

use App\Enums\LeadStatus;
use App\Models\Applicant;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\User;

it('creates a lead with tags and an initial note', function () {
    $user = User::factory()->create();
    $tags = Tag::factory()->count(2)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('leads.store'), [
            'first_name' => 'Ariunaa',
            'last_name' => 'Bat',
            'email' => 'ariunaa@example.com',
            'phone' => '+97699990000',
            'source' => 'website',
            'status' => 'new',
            'country_of_citizenship' => 'Mongolia',
            'interested_visa_type' => 'Student',
            'education_history' => [
                [
                    'institution' => 'National University of Mongolia',
                    'degree' => 'Bachelor',
                    'field_of_study' => 'Computer Science',
                    'start_date' => '2019-09-01',
                    'end_date' => '2023-06-01',
                    'notes' => 'Graduated with strong grades.',
                ],
            ],
            'work_experience' => [
                [
                    'company' => 'Tech LLC',
                    'title' => 'Software Engineer',
                    'location' => 'Ulaanbaatar',
                    'start_date' => '2023-07-01',
                    'end_date' => null,
                    'is_current' => true,
                    'notes' => 'Full-time role in product engineering.',
                ],
            ],
            'tag_ids' => $tags->pluck('id')->all(),
            'note' => 'Requested a callback about a Canadian study permit.',
        ]);

    $response->assertRedirect();

    $lead = Lead::query()->with(['tags', 'notes', 'statusHistories', 'activities'])->first();

    expect($lead)->not->toBeNull();
    expect($lead->first_name)->toBe('Ariunaa');
    expect($lead->status)->toBe(LeadStatus::New);
    expect($lead->education_history)->toHaveCount(1);
    expect($lead->work_experience)->toHaveCount(1);
    expect($lead->tags)->toHaveCount(2);
    expect($lead->notes)->toHaveCount(1);
    expect($lead->statusHistories)->toHaveCount(1);
    expect($lead->activities)->toHaveCount(1);
});

it('updates a lead with education and work experience', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->for($user, 'assignedTo')->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('leads.update', $lead), [
            'first_name' => 'Temuulen',
            'last_name' => 'Enkh',
            'email' => 'temuulen@example.com',
            'phone' => '+97688112233',
            'source' => 'website',
            'country_of_citizenship' => 'Mongolia',
            'interested_visa_type' => 'Skilled Worker',
            'education_history' => [
                [
                    'institution' => 'MUST',
                    'degree' => 'Master',
                    'field_of_study' => 'Data Science',
                    'start_date' => '2020-09-01',
                    'end_date' => '2022-06-01',
                    'notes' => 'Focused on applied analytics.',
                ],
            ],
            'work_experience' => [
                [
                    'company' => 'Nomad Data',
                    'title' => 'Analyst',
                    'location' => 'Ulaanbaatar',
                    'start_date' => '2022-08-01',
                    'end_date' => null,
                    'is_current' => true,
                    'notes' => 'Works on reporting and forecasting.',
                ],
            ],
            'tag_ids' => [],
        ]);

    $response->assertRedirect();

    $lead->refresh();

    expect($lead->first_name)->toBe('Temuulen');
    expect($lead->education_history[0]['degree'])->toBe('Master');
    expect($lead->work_experience[0]['company'])->toBe('Nomad Data');
});

it('converts a lead into an applicant and records the workflow', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->for($user, 'assignedTo')->create([
        'status' => LeadStatus::Qualified,
        'source' => 'referral',
    ]);

    $tags = Tag::factory()->count(2)->create();
    $lead->tags()->sync($tags->pluck('id')->all());

    $response = $this
        ->actingAs($user)
        ->post(route('leads.convert', $lead), [
            'nationality' => 'Mongolia',
            'country_of_residence' => 'Mongolia',
            'passport_number' => 'E1234567',
            'passport_country' => 'Mongolia',
            'passport_issued_at' => '2020-01-01',
            'passport_expires_at' => '2030-01-01',
            'travel_history' => [
                [
                    'country' => 'South Korea',
                    'purpose' => 'Tourism',
                    'year' => 2023,
                ],
            ],
        ]);

    $applicant = Applicant::query()->first();

    $response->assertRedirect(route('applicants.show', $applicant));

    $lead->refresh();
    $applicant->refresh();

    expect($lead->status)->toBe(LeadStatus::Applied);
    expect($lead->converted_to_applicant_id)->toBe($applicant->id);
    expect($applicant->lead_id)->toBe($lead->id);
    expect($applicant->tags()->count())->toBe(2);
    expect($lead->activities()->count())->toBe(2);
    expect($applicant->activities()->count())->toBe(1);
});
