<?php

namespace Tests\Feature\Settings;

use App\Enums\TaskPriority;
use App\Enums\VisaCaseStatus;
use App\Models\User;
use App\Models\VisaCaseTaskTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class VisaCaseTaskTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_template_page_is_displayed_with_default_status_groups(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.task-templates.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/VisaCaseTaskTemplates')
            ->has('templateGroups', 7)
            ->where('templateGroups.0.label', 'Intake')
            ->where('templateGroups.0.tasks.0.title', 'Send intake checklist to client')
            ->where('templateGroups.3.label', 'Submitted')
        );
    }

    public function test_users_can_update_task_templates_for_each_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('settings.task-templates.store'), [
                'templates' => [
                    [
                        'status' => VisaCaseStatus::Intake->value,
                        'tasks' => [
                            [
                                'title' => 'Collect signed service agreement',
                                'description' => 'Confirm the engagement paperwork is completed before the case moves ahead.',
                                'priority' => TaskPriority::High->value,
                                'due_in_days' => 1,
                            ],
                        ],
                    ],
                    [
                        'status' => VisaCaseStatus::DocumentsPending->value,
                        'tasks' => [],
                    ],
                    [
                        'status' => VisaCaseStatus::ReadyToFile->value,
                        'tasks' => [
                            [
                                'title' => 'Run final compliance review',
                                'description' => 'Check every filing answer against the source documents.',
                                'priority' => TaskPriority::Urgent->value,
                                'due_in_days' => 0,
                            ],
                        ],
                    ],
                    [
                        'status' => VisaCaseStatus::Submitted->value,
                        'tasks' => [],
                    ],
                    [
                        'status' => VisaCaseStatus::Approved->value,
                        'tasks' => [],
                    ],
                    [
                        'status' => VisaCaseStatus::Rejected->value,
                        'tasks' => [],
                    ],
                    [
                        'status' => VisaCaseStatus::Closed->value,
                        'tasks' => [],
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('settings.task-templates.index'));

        $templates = VisaCaseTaskTemplate::query()
            ->where('agency_id', $user->agency_id)
            ->orderBy('visa_case_status')
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(2, $templates);
        $this->assertSame(
            ['Collect signed service agreement', 'Run final compliance review'],
            $templates->pluck('title')->all(),
        );
        $this->assertSame(
            [TaskPriority::High->value, TaskPriority::Urgent->value],
            $templates->map(fn (VisaCaseTaskTemplate $template): string => $template->priority->value)->all(),
        );
        $this->assertSame([1, 0], $templates->pluck('due_in_days')->all());
    }
}
