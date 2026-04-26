<?php

namespace Tests\Feature\Settings;

use App\Enums\ClientStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Enums\VisaCaseStatus;
use App\Models\Client;
use App\Models\User;
use App\Models\VisaRequirementTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_update_team_member_roles(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $teammate = User::factory()->create([
            'agency_id' => $admin->agency_id,
            'role' => UserRole::Staff,
        ]);

        $this->actingAs($admin)
            ->patch(route('settings.team.role.update', $teammate), [
                'role' => UserRole::CaseManager->value,
            ])
            ->assertRedirect(route('settings.team.index'));

        $this->assertSame(UserRole::CaseManager, $teammate->fresh()->role);
    }

    public function test_non_admin_users_cannot_access_admin_settings_areas(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $caseManager = User::factory()->create([
            'agency_id' => $admin->agency_id,
            'role' => UserRole::CaseManager,
        ]);

        $this->actingAs($caseManager)->get(route('settings.team.index'))->assertForbidden();
        $this->actingAs($caseManager)->get(route('settings.agency.edit'))->assertForbidden();
        $this->actingAs($caseManager)->get(route('settings.task-templates.index'))->assertForbidden();
    }

    public function test_case_managers_can_manage_clients_and_visa_cases(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $caseManager = User::factory()->create([
            'agency_id' => $admin->agency_id,
            'role' => UserRole::CaseManager,
        ]);
        $this->createRequirementTemplate('Australia', 'Student visa');

        $this->actingAs($caseManager)
            ->post(route('clients.store'), [
                'full_name' => 'Amina Batsukh',
                'email' => 'amina@example.com',
                'phone' => '+97611111111',
                'nationality' => 'Mongolian',
                'destination_country' => 'Australia',
                'lead_source' => 'Referral',
                'status' => ClientStatus::Lead->value,
            ])
            ->assertRedirect(route('clients.index'));

        $client = Client::query()->where('email', 'amina@example.com')->firstOrFail();

        $this->actingAs($caseManager)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $caseManager->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => 'University of Melbourne',
                'status' => VisaCaseStatus::Intake->value,
            ])
            ->assertRedirect();
    }

    public function test_staff_can_manage_tasks_but_not_clients_or_visa_cases(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $staff = User::factory()->create([
            'agency_id' => $admin->agency_id,
            'role' => UserRole::Staff,
        ]);
        $client = Client::factory()->create([
            'agency_id' => $admin->agency_id,
            'owner_id' => $admin->id,
        ]);
        $this->createRequirementTemplate('Australia', 'Student visa');

        $this->actingAs($staff)
            ->post(route('tasks.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $staff->id,
                'title' => 'Review application notes',
                'status' => TaskStatus::Todo->value,
                'priority' => TaskPriority::Medium->value,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'agency_id' => $staff->agency_id,
            'title' => 'Review application notes',
        ]);

        $this->actingAs($staff)
            ->post(route('clients.store'), [
                'full_name' => 'Viewer Target',
                'status' => ClientStatus::Lead->value,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('visa-cases.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $staff->id,
                'visa_type' => 'Student visa',
                'destination_country' => 'Australia',
                'institution_name' => 'University of Sydney',
                'status' => VisaCaseStatus::Intake->value,
            ])
            ->assertForbidden();
    }

    public function test_viewers_can_view_crm_pages_but_cannot_create_records(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $viewer = User::factory()->create([
            'agency_id' => $admin->agency_id,
            'role' => UserRole::Viewer,
        ]);

        $this->actingAs($viewer)->get(route('clients.index'))->assertOk();
        $this->actingAs($viewer)->get(route('visa-cases.index'))->assertOk();
        $this->actingAs($viewer)->get(route('tasks.index'))->assertOk();

        $this->actingAs($viewer)
            ->post(route('tasks.store'), [
                'title' => 'Should fail',
                'status' => TaskStatus::Todo->value,
                'priority' => TaskPriority::Low->value,
            ])
            ->assertForbidden();
    }

    private function createRequirementTemplate(string $countryName, string $visaType): void
    {
        VisaRequirementTemplate::factory()->create([
            'country_name' => $countryName,
            'visa_type' => $visaType,
            'requires_institution_name' => true,
            'is_active' => true,
        ]);
    }
}
