<?php

namespace Tests\Feature\Settings;

use App\Enums\VisaCaseStatus;
use App\Models\Client;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseStatusTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class VisaCaseStatusTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_visa_status_template_page_is_displayed_with_default_statuses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.visa-statuses.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/VisaCaseStatusTemplates')
            ->has('templates', 7)
            ->where('templates.0.status_key', VisaCaseStatus::Intake->value)
            ->where('templates.0.label', VisaCaseStatus::Intake->label())
            ->where('templates.3.status_key', VisaCaseStatus::Submitted->value)
        );
    }

    public function test_users_can_update_company_visa_status_labels(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('settings.visa-statuses.store'), [
                'templates' => [
                    ['status_key' => VisaCaseStatus::Intake->value, 'label' => 'New enquiry'],
                    ['status_key' => VisaCaseStatus::DocumentsPending->value, 'label' => 'Waiting on docs'],
                    ['status_key' => VisaCaseStatus::ReadyToFile->value, 'label' => 'Ready for lodge'],
                    ['status_key' => VisaCaseStatus::Submitted->value, 'label' => 'Filed with embassy'],
                    ['status_key' => VisaCaseStatus::Approved->value, 'label' => 'Granted'],
                    ['status_key' => VisaCaseStatus::Rejected->value, 'label' => 'Refused'],
                    ['status_key' => VisaCaseStatus::Closed->value, 'label' => 'Archived'],
                ],
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('settings.visa-statuses.index'));

        $this->assertSame(
            [
                'New enquiry',
                'Waiting on docs',
                'Ready for lodge',
                'Filed with embassy',
                'Granted',
                'Refused',
                'Archived',
            ],
            VisaCaseStatusTemplate::query()
                ->where('agency_id', $user->agency_id)
                ->orderBy('sort_order')
                ->pluck('label')
                ->all(),
        );
    }

    public function test_custom_status_labels_are_used_in_visa_case_pages_and_task_templates(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);

        VisaCaseStatusTemplate::factory()->create([
            'agency_id' => $user->agency_id,
            'status_key' => VisaCaseStatus::Submitted->value,
            'label' => 'Filed with embassy',
            'sort_order' => 4,
        ]);

        VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'reference_code' => 'CASE-1001',
            'status' => VisaCaseStatus::Submitted,
        ]);

        $this->actingAs($user)
            ->get(route('visa-cases.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('visa-cases/Index')
                ->where('visaCases.0.status_label', 'Filed with embassy')
                ->where('statusOptions.3.label', 'Filed with embassy')
            );

        $this->actingAs($user)
            ->get(route('settings.task-templates.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('settings/VisaCaseTaskTemplates')
                ->where('templateGroups.3.label', 'Filed with embassy')
            );
    }
}
