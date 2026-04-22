<?php

namespace Tests\Feature\Settings;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Task;
use App\Models\TaskStatusTemplate;
use App\Models\User;
use App\Models\VisaCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TaskStatusTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_status_template_page_is_displayed_with_default_statuses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.task-statuses.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/TaskStatusTemplates')
            ->has('templates', 3)
            ->where('templates.0.status_key', TaskStatus::Todo->value)
            ->where('templates.0.label', TaskStatus::Todo->label())
            ->where('templates.1.status_key', TaskStatus::InProgress->value)
        );
    }

    public function test_users_can_update_company_task_status_labels(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('settings.task-statuses.store'), [
                'templates' => [
                    ['status_key' => TaskStatus::Todo->value, 'label' => 'Queued'],
                    ['status_key' => TaskStatus::InProgress->value, 'label' => 'Working'],
                    ['status_key' => TaskStatus::Done->value, 'label' => 'Delivered'],
                ],
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('settings.task-statuses.index'));

        $this->assertSame(
            ['Queued', 'Working', 'Delivered'],
            TaskStatusTemplate::query()
                ->where('agency_id', $user->agency_id)
                ->orderBy('sort_order')
                ->pluck('label')
                ->all(),
        );
    }

    public function test_custom_task_status_labels_are_used_in_task_pages(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $user->agency_id,
            'owner_id' => $user->id,
        ]);
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'assigned_user_id' => $user->id,
            'reference_code' => 'CASE-3001',
        ]);

        TaskStatusTemplate::factory()->create([
            'agency_id' => $user->agency_id,
            'status_key' => TaskStatus::InProgress->value,
            'label' => 'Working',
            'sort_order' => 2,
        ]);

        Task::factory()->create([
            'agency_id' => $user->agency_id,
            'client_id' => $client->id,
            'visa_case_id' => $visaCase->id,
            'assigned_user_id' => $user->id,
            'created_by_id' => $user->id,
            'title' => 'Prepare forms',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::High,
        ]);

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('tasks/Index')
                ->where('tasks.0.status_label', 'Working')
                ->where('statusOptions.1.label', 'Working')
            );
    }
}
