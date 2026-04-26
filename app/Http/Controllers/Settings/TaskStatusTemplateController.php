<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreTaskStatusTemplateRequest;
use App\Support\TaskStatusTemplateResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskStatusTemplateController extends Controller
{
    public function __construct(private TaskStatusTemplateResolver $taskStatusTemplateResolver) {}

    public function index(): Response
    {
        $agency = request()->user()?->agency;
        abort_if($agency === null || ! request()->user()?->canManageWorkflowSettings(), 403);

        return Inertia::render('settings/TaskStatusTemplates', [
            'templates' => $this->taskStatusTemplateResolver->templates($agency)
                ->map(fn ($template): array => [
                    'status_key' => $template->status_key,
                    'label' => $template->label,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(StoreTaskStatusTemplateRequest $request): RedirectResponse
    {
        $agency = $request->user()?->agency;
        abort_if($agency === null || ! $request->user()?->canManageWorkflowSettings(), 403);

        foreach ($request->validated('templates') as $index => $template) {
            $agency->taskStatusTemplates()->updateOrCreate(
                ['status_key' => $template['status_key']],
                [
                    'label' => trim($template['label']),
                    'sort_order' => $index + 1,
                ],
            );
        }

        return to_route('settings.task-statuses.index')->with('success', 'Task statuses updated.');
    }
}
