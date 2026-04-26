<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\VisaCaseStatus;
use App\Enums\VisaRequirementStatus;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseTaskTemplate;
use App\Models\VisaRequirementItem;
use App\Models\VisaRequirementTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CrmWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_basic_crm_records(): void
    {
        $user = User::factory()->create();
        $this->createRequirementTemplate('Australia', 'Student visa');

        $this->actingAs($user);

        $this->post(route('clients.store'), [
            'full_name' => 'Amina Batsukh',
            'email' => 'amina@example.com',
            'phone' => '+97611111111',
            'nationality' => 'Mongolian',
            'destination_country' => 'Australia',
            'lead_source' => 'Referral',
            'status' => ClientStatus::Lead->value,
            'notes' => 'Looking for a student visa pathway.',
        ])->assertRedirect(route('clients.index'));

        $client = Client::query()->where('email', 'amina@example.com')->firstOrFail();

        $this->assertSame($user->agency_id, $client->agency_id);

        $response = $this->post(route('visa-cases.store'), [
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'visa_type' => 'Student visa',
            'destination_country' => 'Australia',
            'institution_name' => 'University of Melbourne',
            'status' => VisaCaseStatus::Intake->value,
            'notes' => 'Collecting financial documents.',
        ]);

        $visaCase = VisaCase::query()->where('client_id', $client->id)->firstOrFail();
        $response->assertRedirect(route('visa-cases.show', $visaCase));

        $this->assertSame($user->agency_id, $visaCase->agency_id);
        $this->assertSame('University of Melbourne', $visaCase->institution_name);

        $this->post(route('tasks.store'), [
            'client_id' => $client->id,
            'visa_case_id' => $visaCase->id,
            'assigned_user_id' => $user->id,
            'title' => 'Collect bank statement',
            'description' => 'Need last 6 months of statements.',
            'status' => TaskStatus::Todo->value,
            'priority' => TaskPriority::High->value,
            'due_at' => now()->addDay()->toDateTimeString(),
        ])->assertRedirect(route('tasks.index'));

        $task = Task::query()->where('title', 'Collect bank statement')->firstOrFail();

        $this->assertSame($user->agency_id, $task->agency_id);
        $this->assertSame($client->id, $task->client_id);
        $this->assertSame($visaCase->id, $task->visa_case_id);
    }

    public function test_default_tasks_are_created_when_a_visa_case_is_created(): void
    {
        $user = User::factory()->create();
        $this->createRequirementTemplate('Australia', 'Student visa');
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        VisaCaseTaskTemplate::factory()->create([
            'agency_id' => $user->agency_id,
            'visa_case_status' => VisaCaseStatus::Intake,
            'title' => 'Collect passport copy',
            'description' => 'Confirm the passport bio page is on file.',
            'priority' => TaskPriority::High,
            'due_in_days' => 1,
            'sort_order' => 1,
        ]);
        VisaCaseTaskTemplate::factory()->create([
            'agency_id' => $user->agency_id,
            'visa_case_status' => VisaCaseStatus::Intake,
            'title' => 'Review intake questionnaire',
            'description' => 'Check the client answers before requesting more documents.',
            'priority' => TaskPriority::Medium,
            'due_in_days' => 3,
            'sort_order' => 2,
        ]);
        VisaCaseTaskTemplate::factory()->create([
            'agency_id' => $user->agency_id,
            'visa_case_status' => VisaCaseStatus::Submitted,
            'title' => 'Ignore for intake creation',
            'description' => 'Should not be created until the case is submitted.',
            'priority' => TaskPriority::Low,
            'due_in_days' => 7,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => 'Monash University',
                'status' => VisaCaseStatus::Intake->value,
                'notes' => 'Collecting financial documents.',
            ]);

        $visaCase = VisaCase::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('visa-cases.show', $visaCase));

        $tasks = $visaCase->tasks()->orderBy('id')->get();

        $this->assertCount(2, $tasks);
        $this->assertSame(
            [
                'Collect passport copy',
                'Review intake questionnaire',
            ],
            $tasks->pluck('title')->all(),
        );
        $this->assertSame(
            [
                $user->agency_id,
                $user->agency_id,
            ],
            $tasks->pluck('agency_id')->all(),
        );
        $this->assertSame(
            [
                $client->id,
                $client->id,
            ],
            $tasks->pluck('client_id')->all(),
        );
        $this->assertSame(
            [
                $user->id,
                $user->id,
            ],
            $tasks->pluck('assigned_user_id')->all(),
        );
        $this->assertSame(
            [
                $user->id,
                $user->id,
            ],
            $tasks->pluck('created_by_id')->all(),
        );
        $this->assertSame(
            [
                TaskStatus::Todo->value,
                TaskStatus::Todo->value,
            ],
            $tasks->map(fn (Task $task): string => $task->status->value)->all(),
        );
        $this->assertSame(
            [
                TaskPriority::High->value,
                TaskPriority::Medium->value,
            ],
            $tasks->map(fn (Task $task): string => $task->priority->value)->all(),
        );
        $this->assertTrue($tasks->every(fn (Task $task): bool => $task->visa_case_id === $visaCase->id));
        $this->assertTrue($tasks->every(fn (Task $task): bool => $task->due_at !== null));
        $this->assertTrue($tasks->every(fn (Task $task): bool => $task->visa_case_task_template_id !== null));
    }

    public function test_status_specific_tasks_are_created_once_when_a_visa_case_changes_status(): void
    {
        $user = User::factory()->create();
        $this->createRequirementTemplate('Australia', 'Student visa');
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        VisaCaseTaskTemplate::factory()->create([
            'agency_id' => $user->agency_id,
            'visa_case_status' => VisaCaseStatus::Intake,
            'title' => 'Collect passport copy',
            'priority' => TaskPriority::High,
            'due_in_days' => 1,
            'sort_order' => 1,
        ]);
        VisaCaseTaskTemplate::factory()->create([
            'agency_id' => $user->agency_id,
            'visa_case_status' => VisaCaseStatus::Submitted,
            'title' => 'Send submission receipt',
            'priority' => TaskPriority::Medium,
            'due_in_days' => 1,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)->post(route('visa-cases.store'), [
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'visa_type' => 'Student visa',
            'destination_country' => 'Australia',
            'institution_name' => 'University of Sydney',
            'status' => VisaCaseStatus::Intake->value,
        ]);

        $visaCase = VisaCase::query()->latest('id')->firstOrFail();

        $this->assertSame(['Collect passport copy'], $visaCase->tasks()->orderBy('id')->pluck('title')->all());

        $this->actingAs($user)
            ->patch(route('visa-cases.update', $visaCase), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => 'University of Sydney',
                'status' => VisaCaseStatus::Submitted->value,
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $visaCase->refresh();

        $this->assertSame(
            ['Collect passport copy', 'Send submission receipt'],
            $visaCase->tasks()->orderBy('id')->pluck('title')->all(),
        );

        $this->actingAs($user)
            ->patch(route('visa-cases.update', $visaCase), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => 'University of Sydney',
                'status' => VisaCaseStatus::Intake->value,
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $visaCase->refresh();

        $this->assertCount(2, $visaCase->tasks);
    }

    public function test_users_only_see_clients_from_their_own_agency(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstClient = Client::factory()->create([
            'agency_id' => $firstUser->agency_id,
            'owner_id' => $firstUser->id,
            'full_name' => 'Agency One Client',
        ]);

        Client::factory()->create([
            'agency_id' => $secondUser->agency_id,
            'owner_id' => $secondUser->id,
            'full_name' => 'Agency Two Client',
        ]);

        $response = $this->actingAs($firstUser)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee($firstClient->full_name);
        $response->assertDontSee('Agency Two Client');
    }

    public function test_users_cannot_update_tasks_from_another_agency(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $task = Task::factory()->create([
            'agency_id' => $secondUser->agency_id,
            'created_by_id' => $secondUser->id,
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
        ]);

        $this->actingAs($firstUser)
            ->patch(route('tasks.update', $task), [
                'status' => TaskStatus::Done->value,
            ])
            ->assertForbidden();
    }

    public function test_client_timeline_includes_notes_and_updates_in_reverse_chronological_order(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('clients.notes.store', $client), [
                'body' => 'Requested updated employment letter from the client.',
            ])
            ->assertRedirect(route('clients.show', $client));

        $note = Note::query()->firstOrFail();

        $this->assertSame($client->agency_id, $note->agency_id);
        $this->assertSame($client->id, $note->notable_id);
        $this->assertSame('client', $note->notable_type);

        $this->travel(5)->minutes();

        $this->actingAs($user)
            ->patch(route('clients.update', $client), [
                'full_name' => $client->full_name,
                'email' => 'timeline@example.com',
                'phone' => $client->phone,
                'date_of_birth' => $client->date_of_birth?->toDateString(),
                'passport_number' => $client->passport_number,
                'passport_expiry_date' => $client->passport_expiry_date?->toDateString(),
                'marital_status' => $client->marital_status,
                'occupation' => $client->occupation,
                'current_address' => $client->current_address,
                'nationality' => $client->nationality,
                'destination_country' => $client->destination_country,
                'lead_source' => $client->lead_source,
                'status' => $client->status->value,
                'family_members' => $client->family_members ?? [],
                'education_history' => $client->education_history ?? [],
                'work_experiences' => $client->work_experiences ?? [],
            ])
            ->assertRedirect(route('clients.show', $client));

        $this->actingAs($user)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('timeline.0.title', 'Client updated')
                ->where('timeline.0.meta.author', $user->name)
                ->where('timeline.1.title', 'Note added')
                ->where('timeline.1.description', 'Requested updated employment letter from the client.')
            );
    }

    public function test_users_can_update_clients_in_their_own_agency(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'status' => ClientStatus::Lead,
        ]);

        $this->actingAs($user)
            ->patch(route('clients.update', $client), [
                'full_name' => 'Updated Client Name',
                'email' => 'updated@example.com',
                'phone' => '+97699990000',
                'date_of_birth' => '1998-01-14',
                'passport_number' => 'E1234567',
                'passport_expiry_date' => '2030-06-01',
                'marital_status' => 'single',
                'occupation' => 'Marketing Manager',
                'current_address' => 'Ulaanbaatar, Mongolia',
                'nationality' => 'Mongolian',
                'destination_country' => 'Australia',
                'lead_source' => 'Website',
                'status' => ClientStatus::Active->value,
                'family_members' => [
                    [
                        'relationship' => 'Mother',
                        'full_name' => 'Narantuya Bat',
                        'date_of_birth' => '1972-04-21',
                        'nationality' => 'Mongolian',
                        'occupation' => 'Teacher',
                        'is_accompanying' => false,
                    ],
                ],
                'education_history' => [
                    [
                        'institution' => 'National University of Mongolia',
                        'qualification' => 'Bachelor',
                        'field_of_study' => 'Marketing',
                        'country' => 'Mongolia',
                        'start_date' => '2016-09-01',
                        'end_date' => '2020-06-01',
                        'is_current' => false,
                    ],
                ],
                'work_experiences' => [
                    [
                        'employer' => 'Blue Sky LLC',
                        'job_title' => 'Marketing Manager',
                        'country' => 'Mongolia',
                        'start_date' => '2021-02-01',
                        'end_date' => '2024-12-01',
                        'is_current' => false,
                        'summary' => 'Led campaigns and customer acquisition.',
                    ],
                ],
            ])
            ->assertRedirect(route('clients.show', $client));

        $client->refresh();

        $this->assertSame('Updated Client Name', $client->full_name);
        $this->assertSame('updated@example.com', $client->email);
        $this->assertSame(ClientStatus::Active, $client->status);
        $this->assertSame('E1234567', $client->passport_number);
        $this->assertSame('Marketing Manager', $client->occupation);
        $this->assertSame('Mother', $client->family_members[0]['relationship']);
        $this->assertSame('National University of Mongolia', $client->education_history[0]['institution']);
        $this->assertSame('Blue Sky LLC', $client->work_experiences[0]['employer']);

        $this->actingAs($user)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('E1234567')
            ->assertSee('National University of Mongolia')
            ->assertSee('Blue Sky LLC')
            ->assertSee('Narantuya Bat');
    }

    public function test_users_can_update_visa_cases_in_their_own_agency(): void
    {
        $user = User::factory()->create();
        $this->createRequirementTemplate('Japan', 'Tourist visa');
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'status' => VisaCaseStatus::Intake,
        ]);

        $this->actingAs($user)
            ->patch(route('visa-cases.update', $visaCase), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Tourist visa',
                'destination_country' => 'Japan',
                'institution_name' => 'Should be cleared',
                'status' => VisaCaseStatus::Submitted->value,
                'submitted_at' => now()->toDateString(),
                'decision_at' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $visaCase->refresh();

        $this->assertSame('Tourist visa', $visaCase->visa_type);
        $this->assertSame('Japan', $visaCase->destination_country);
        $this->assertNull($visaCase->institution_name);
        $this->assertSame(VisaCaseStatus::Submitted, $visaCase->status);

        $this->actingAs($user)
            ->get(route('visa-cases.show', $visaCase))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('timeline.0.title', 'Visa case updated')
                ->where('timeline.0.meta.author', $user->name)
            );
    }

    public function test_visa_cases_require_an_institution_name_when_the_template_demands_it(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);

        VisaRequirementTemplate::factory()->create([
            'country_name' => 'Australia',
            'visa_type' => 'Study permit',
            'requires_institution_name' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Study permit',
                'destination_country' => 'Australia',
                'institution_name' => '',
                'status' => VisaCaseStatus::Intake->value,
            ])
            ->assertSessionHasErrors('institution_name');
    }

    public function test_student_named_visa_cases_do_not_require_an_institution_name_when_the_template_does_not_demand_it(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);

        VisaRequirementTemplate::factory()->create([
            'country_name' => 'Australia',
            'visa_type' => 'Student visa',
            'requires_institution_name' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => '',
                'status' => VisaCaseStatus::Intake->value,
            ])
            ->assertSessionHasNoErrors();

        $visaCase = VisaCase::query()->latest('id')->firstOrFail();

        $this->assertNull($visaCase->institution_name);
    }

    public function test_visa_case_creation_requires_a_valid_country_and_visa_library_match(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);

        $this->createRequirementTemplate('Germany', 'Tourist visa');

        $this->actingAs($user)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Germany',
                'status' => VisaCaseStatus::Intake->value,
            ])
            ->assertSessionHasErrors('visa_type');
    }

    public function test_users_can_upload_private_attachments_to_visa_cases_and_download_them(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('passport.pdf', 120, 'application/pdf');

        $this->actingAs($user)
            ->post(route('visa-cases.attachments.store', $visaCase), [
                'attachment' => $file,
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $attachment = Attachment::query()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($user)
            ->get(route('attachments.show', $attachment))
            ->assertOk();
    }

    public function test_matching_requirement_templates_are_synced_to_visa_cases(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        $template = VisaRequirementTemplate::factory()->create([
            'region' => 'Europe',
            'country_name' => 'Germany',
            'visa_type' => 'Tourist visa',
            'label' => 'Germany tourist visa starter checklist',
        ]);

        VisaRequirementItem::factory()->create([
            'visa_requirement_template_id' => $template->id,
            'label' => 'Passport valid for travel period',
            'sort_order' => 1,
        ]);
        VisaRequirementItem::factory()->create([
            'visa_requirement_template_id' => $template->id,
            'label' => 'Proof of funds',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($user)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Tourist visa',
                'destination_country' => 'Germany',
                'status' => VisaCaseStatus::Intake->value,
            ]);

        $visaCase = VisaCase::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('visa-cases.show', $visaCase));

        $this->assertCount(2, $visaCase->requirements);

        $this->actingAs($user)
            ->get(route('visa-cases.show', $visaCase))
            ->assertOk()
            ->assertSee('Passport valid for travel period')
            ->assertSee('Proof of funds');
    }

    public function test_users_can_mark_visa_requirements_as_completed(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        $template = VisaRequirementTemplate::factory()->create([
            'country_name' => 'Australia',
            'visa_type' => 'Student visa',
        ]);
        $templateItem = VisaRequirementItem::factory()->create([
            'visa_requirement_template_id' => $template->id,
            'label' => 'Confirmation of enrolment',
            'sort_order' => 1,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'visa_type' => 'Student visa',
            'destination_country' => 'Australia',
        ]);

        $visaCase->requirements()->create([
            'visa_requirement_item_id' => $templateItem->id,
            'label' => $templateItem->label,
            'help_text' => null,
            'is_required' => true,
            'is_completed' => false,
            'sort_order' => 1,
        ]);

        $requirement = $visaCase->requirements()->firstOrFail();

        $this->actingAs($user)
            ->patch(route('visa-cases.requirements.update', [$visaCase, $requirement]), [
                'is_completed' => true,
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $requirement->refresh();

        $this->assertTrue($requirement->is_completed);
        $this->assertNotNull($requirement->completed_at);
        $this->assertSame(VisaRequirementStatus::Verified, $requirement->status);
        $this->assertNotNull($requirement->reviewed_at);
    }

    public function test_users_can_update_requirement_status_due_date_and_upload_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        $template = VisaRequirementTemplate::factory()->create([
            'country_name' => 'Australia',
            'visa_type' => 'Visitor visa',
        ]);
        $templateItem = VisaRequirementItem::factory()->create([
            'visa_requirement_template_id' => $template->id,
            'category' => 'Identity',
            'label' => 'Passport biodata page',
            'sort_order' => 1,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'visa_type' => 'Visitor visa',
            'destination_country' => 'Australia',
        ]);

        $requirement = $visaCase->requirements()->create([
            'visa_requirement_item_id' => $templateItem->id,
            'category' => $templateItem->category,
            'label' => $templateItem->label,
            'help_text' => null,
            'is_required' => true,
            'status' => VisaRequirementStatus::Pending,
            'is_completed' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('visa-cases.requirements.update', [$visaCase, $requirement]), [
                'status' => VisaRequirementStatus::Requested->value,
                'due_at' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $requirement->refresh();

        $this->assertSame(VisaRequirementStatus::Requested, $requirement->status);
        $this->assertNotNull($requirement->requested_at);
        $this->assertSame(now()->addDays(5)->toDateString(), $requirement->due_at?->toDateString());

        $file = UploadedFile::fake()->create('passport.pdf', 120, 'application/pdf');

        $this->actingAs($user)
            ->post(route('visa-cases.requirements.attachments.store', [$visaCase, $requirement]), [
                'attachment' => $file,
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $attachment = $requirement->attachments()->first();

        $this->assertNotNull($attachment);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_users_cannot_download_attachments_from_another_agency(): void
    {
        Storage::fake('local');

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $secondUser->agency_id,
            'owner_id' => $secondUser->id,
        ]);
        $attachment = $client->attachments()->create([
            'agency_id' => $secondUser->agency_id,
            'uploaded_by_id' => $secondUser->id,
            'disk' => 'local',
            'path' => 'attachments/test-file.pdf',
            'original_name' => 'test-file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        Storage::disk('local')->put($attachment->path, 'secret document');

        $this->actingAs($firstUser)
            ->get(route('attachments.show', $attachment))
            ->assertForbidden();
    }

    private function createRequirementTemplate(string $countryName, string $visaType): VisaRequirementTemplate
    {
        return VisaRequirementTemplate::factory()->create([
            'country_name' => $countryName,
            'visa_type' => $visaType,
            'requires_institution_name' => str_contains(strtolower($visaType), 'student') || str_contains(strtolower($visaType), 'study'),
            'is_active' => true,
        ]);
    }
}
