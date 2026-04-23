<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\VisaCaseStatus;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaRequirementTemplate;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\VisaCaseAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_assignment_creates_a_database_notification_for_the_assignee(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create([
            'agency_id' => $creator->agency_id,
        ]);
        $client = Client::factory()->create([
            'agency_id' => $creator->agency_id,
            'owner_id' => $creator->id,
            'status' => ClientStatus::Active,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $creator->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $creator->id,
        ]);

        $this->actingAs($creator)
            ->post(route('tasks.store'), [
                'client_id' => $client->id,
                'visa_case_id' => $visaCase->id,
                'assigned_user_id' => $assignee->id,
                'title' => 'Collect passport scan',
                'description' => 'Need the bio page in color.',
                'status' => TaskStatus::Todo->value,
                'priority' => TaskPriority::High->value,
                'due_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertRedirect(route('tasks.index'));

        $notification = $assignee->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('task_assigned', $notification->data['kind']);
        $this->assertSame('New task assigned', $notification->data['title']);
        $this->assertSame(route('visa-cases.show', $visaCase), $notification->data['action_url']);
        $this->assertSame('Collect passport scan', $notification->data['resource_label']);
    }

    public function test_visa_case_reassignment_notifies_the_new_assignee(): void
    {
        $this->createRequirementTemplate('Australia', 'Student visa');

        $creator = User::factory()->create();
        $assignee = User::factory()->create([
            'agency_id' => $creator->agency_id,
        ]);
        $client = Client::factory()->create([
            'agency_id' => $creator->agency_id,
            'owner_id' => $creator->id,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $creator->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $creator->id,
            'visa_type' => 'Student visa',
            'destination_country' => 'Australia',
            'institution_name' => 'University of Melbourne',
            'status' => VisaCaseStatus::Intake,
        ]);

        $this->actingAs($creator)
            ->patch(route('visa-cases.update', $visaCase), [
                'client_id' => $client->id,
                'assigned_user_id' => $assignee->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => 'University of Melbourne',
                'status' => VisaCaseStatus::Intake->value,
            ])
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $notification = $assignee->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('visa_case_assigned', $notification->data['kind']);
        $this->assertSame(route('visa-cases.show', $visaCase), $notification->data['action_url']);
        $this->assertSame($visaCase->reference_code, $notification->data['resource_label']);
    }

    public function test_self_assigned_task_creation_still_shows_a_notification(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'status' => ClientStatus::Active,
        ]);

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'title' => 'Review intake notes',
                'description' => 'Check the client summary before the call.',
                'status' => TaskStatus::Todo->value,
                'priority' => TaskPriority::Medium->value,
            ])
            ->assertRedirect(route('tasks.index'));

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('task_assigned', $notification->data['kind']);
        $this->assertSame('Task created', $notification->data['title']);
        $this->assertSame('Review intake notes', $notification->data['resource_label']);
    }

    public function test_self_assigned_visa_case_creation_still_shows_a_notification(): void
    {
        $this->createRequirementTemplate('Australia', 'Student visa');

        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $user->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => 'University of Sydney',
                'status' => VisaCaseStatus::Intake->value,
            ]);

        $visaCase = VisaCase::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('visa-cases.show', $visaCase));

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('visa_case_assigned', $notification->data['kind']);
        $this->assertSame('Visa case created', $notification->data['title']);
        $this->assertSame($visaCase->reference_code, $notification->data['resource_label']);
    }

    public function test_shared_notifications_are_available_in_inertia_props(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $actor->agency_id,
        ]);
        $client = Client::factory()->create([
            'agency_id' => $actor->agency_id,
            'owner_id' => $actor->id,
            'full_name' => 'Amina Batsukh',
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $actor->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'reference_code' => 'CASE-ALERT',
        ]);

        $user->notify(new VisaCaseAssignedNotification($visaCase, $actor));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.unread_count', 1)
                ->has('notifications.items', 1)
                ->where('notifications.items.0.kind', 'visa_case_assigned')
                ->where('notifications.items.0.title', 'Visa case assigned')
                ->where('notifications.items.0.context', 'Amina Batsukh')
            );
    }

    public function test_users_can_open_and_mark_notifications_as_read(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $actor->agency_id,
        ]);
        $client = Client::factory()->create([
            'agency_id' => $actor->agency_id,
            'owner_id' => $actor->id,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $actor->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
        ]);
        $task = Task::factory()->create([
            'agency_id' => $actor->agency_id,
            'client_id' => $client->id,
            'visa_case_id' => $visaCase->id,
            'assigned_user_id' => $user->id,
            'created_by_id' => $actor->id,
            'title' => 'Verify passport details',
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
        ]);

        $user->notify(new VisaCaseAssignedNotification($visaCase, $actor));
        $user->notify(new TaskAssignedNotification($task, $actor));

        $notifications = $user->notifications()->latest()->get();
        $firstNotification = $notifications->firstOrFail();
        $secondNotification = $notifications->last();

        $this->assertNotNull($secondNotification);

        $this->actingAs($user)
            ->post(route('notifications.open', $firstNotification))
            ->assertRedirect(route('visa-cases.show', $visaCase));

        $this->assertNotNull($firstNotification->fresh()->read_at);
        $this->assertNull($secondNotification->fresh()->read_at);

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_users_cannot_mark_someone_elses_notification_as_read(): void
    {
        $actor = User::factory()->create();
        $owner = User::factory()->create([
            'agency_id' => $actor->agency_id,
        ]);
        $intruder = User::factory()->create([
            'agency_id' => $actor->agency_id,
        ]);
        $client = Client::factory()->create([
            'agency_id' => $actor->agency_id,
            'owner_id' => $actor->id,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $actor->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $owner->id,
        ]);

        $owner->notify(new VisaCaseAssignedNotification($visaCase, $actor));

        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($intruder)
            ->post(route('notifications.read', $notification))
            ->assertNotFound();
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
