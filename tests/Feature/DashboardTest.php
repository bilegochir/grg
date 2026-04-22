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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'status' => ClientStatus::Active,
        ]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('totalClients');
    }

    public function test_dashboard_shows_recent_dashboard_items()
    {
        $user = User::factory()->create();

        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
            'full_name' => 'Amina Batsukh',
            'destination_country' => 'Australia',
            'status' => ClientStatus::Active,
        ]);

        Task::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'created_by_id' => $user->id,
            'title' => 'Collect bank statement',
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::High,
            'due_at' => now()->addDay(),
        ]);

        VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'reference_code' => 'VC-1001',
            'visa_type' => 'Student visa',
            'destination_country' => 'Australia',
            'status' => VisaCaseStatus::Submitted,
        ]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('recentClients', 1)
            ->where('recentClients.0.full_name', 'Amina Batsukh')
            ->where('recentClients.0.destination_country', 'Australia')
            ->where('recentClients.0.status', ClientStatus::Active->value)
            ->where('recentClients.0.status_label', ClientStatus::Active->label())
            ->has('upcomingTasks', 1)
            ->where('upcomingTasks.0.title', 'Collect bank statement')
            ->where('upcomingTasks.0.status', TaskStatus::Todo->value)
            ->where('upcomingTasks.0.priority', TaskPriority::High->value)
            ->where('upcomingTasks.0.client_name', 'Amina Batsukh')
            ->has('pipeline', 1)
            ->where('pipeline.0.reference_code', 'VC-1001')
            ->where('pipeline.0.status', VisaCaseStatus::Submitted->value)
            ->where('pipeline.0.status_label', VisaCaseStatus::Submitted->label())
            ->where('pipeline.0.client_name', 'Amina Batsukh')
        );
    }
}
