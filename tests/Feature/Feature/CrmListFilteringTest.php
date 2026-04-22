<?php

namespace Tests\Feature\Feature;

use App\Enums\ClientStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\VisaCaseStatus;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Models\VisaCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmListFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_index_filters_by_search_and_status(): void
    {
        $user = User::factory()->create();

        Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'full_name' => 'Amina Batsukh',
            'email' => 'amina@example.com',
            'status' => ClientStatus::Active,
        ]);
        Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'full_name' => 'Bat-Erdene Naran',
            'email' => 'bat@example.com',
            'status' => ClientStatus::Lead,
        ]);

        $response = $this->actingAs($user)->get(route('clients.index', [
            'search' => 'Amina',
            'status' => ClientStatus::Active->value,
        ]));

        $response->assertOk();
        $response->assertSee('Amina Batsukh');
        $response->assertDontSee('Bat-Erdene Naran');
    }

    public function test_visa_cases_index_filters_by_search_status_and_country(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'full_name' => 'Case Client',
        ]);

        VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'reference_code' => 'GER-001',
            'visa_type' => 'Tourist visa',
            'destination_country' => 'Germany',
            'status' => VisaCaseStatus::Submitted,
        ]);
        VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'reference_code' => 'AUS-001',
            'visa_type' => 'Student visa',
            'destination_country' => 'Australia',
            'status' => VisaCaseStatus::Intake,
        ]);

        $response = $this->actingAs($user)->get(route('visa-cases.index', [
            'search' => 'GER-001',
            'status' => VisaCaseStatus::Submitted->value,
            'country' => 'Germany',
        ]));

        $response->assertOk();
        $response->assertSee('GER-001');
        $response->assertDontSee('AUS-001');
    }

    public function test_tasks_index_filters_by_search_status_and_priority(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'full_name' => 'Task Client',
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'reference_code' => 'TASK-001',
        ]);

        Task::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'visa_case_id' => $visaCase->id,
            'assigned_user_id' => $user->id,
            'created_by_id' => $user->id,
            'title' => 'Collect financial proof',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::High,
        ]);
        Task::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'visa_case_id' => $visaCase->id,
            'assigned_user_id' => $user->id,
            'created_by_id' => $user->id,
            'title' => 'Send welcome email',
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Low,
        ]);

        $response = $this->actingAs($user)->get(route('tasks.index', [
            'search' => 'financial',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::High->value,
        ]));

        $response->assertOk();
        $response->assertSee('Collect financial proof');
        $response->assertDontSee('Send welcome email');
    }
}
