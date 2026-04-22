<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreVisaCaseTaskTemplateRequest;
use App\Models\Agency;
use App\Models\VisaCaseTaskTemplate;
use App\Support\DefaultVisaCaseTaskTemplateSeeder;
use App\Support\VisaCaseStatusTemplateResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VisaCaseTaskTemplateController extends Controller
{
    public function __construct(
        private DefaultVisaCaseTaskTemplateSeeder $defaultVisaCaseTaskTemplateSeeder,
        private VisaCaseStatusTemplateResolver $visaCaseStatusTemplateResolver,
    )
    {
    }

    public function index(): Response
    {
        $agency = request()->user()?->agency;
        abort_if($agency === null, 403);

        $this->defaultVisaCaseTaskTemplateSeeder->seed($agency);

        return Inertia::render('settings/VisaCaseTaskTemplates', [
            'templateGroups' => $this->templateGroups($agency),
        ]);
    }

    public function store(StoreVisaCaseTaskTemplateRequest $request): RedirectResponse
    {
        $agency = $request->user()?->agency;
        abort_if($agency === null, 403);

        DB::transaction(function () use ($agency, $request): void {
            foreach ($request->validated('templates') as $templateGroup) {
                foreach ($templateGroup['tasks'] as $index => $taskTemplate) {
                    $agency->visaCaseTaskTemplates()->updateOrCreate(
                        [
                            'visa_case_status' => $templateGroup['status'],
                            'sort_order' => $index + 1,
                        ],
                        [
                            'title' => trim($taskTemplate['title']),
                            'description' => filled($taskTemplate['description']) ? trim($taskTemplate['description']) : null,
                            'priority' => $taskTemplate['priority'],
                            'due_in_days' => $taskTemplate['due_in_days'],
                        ],
                    );
                }

                $agency->visaCaseTaskTemplates()
                    ->where('visa_case_status', $templateGroup['status'])
                    ->where('sort_order', '>', count($templateGroup['tasks']))
                    ->delete();
            }
        });

        return to_route('settings.task-templates.index')->with('success', 'Task templates updated.');
    }

    /**
     * @return list<array{
     *     label: string,
     *     status: string,
     *     tasks: list<array{
     *         id: int,
     *         title: string,
     *         description: null|string,
     *         priority: string,
     *         due_in_days: null|int
     *     }>
     * }>
     */
    private function templateGroups(Agency $agency): array
    {
        $statusLabels = $this->visaCaseStatusTemplateResolver->labelsByStatus($agency);

        return collect(\App\Enums\VisaCaseStatus::cases())
            ->map(function (\App\Enums\VisaCaseStatus $status) use ($agency, $statusLabels): array {
                $templates = VisaCaseTaskTemplate::query()
                    ->where('agency_id', $agency->id)
                    ->where('visa_case_status', $status->value)
                    ->orderBy('sort_order')
                    ->get();

                return [
                    'label' => $statusLabels[$status->value] ?? $status->label(),
                    'status' => $status->value,
                    'tasks' => $templates->map(fn (VisaCaseTaskTemplate $template): array => [
                        'id' => $template->id,
                        'title' => $template->title,
                        'description' => $template->description,
                        'priority' => $template->priority->value,
                        'due_in_days' => $template->due_in_days,
                    ])->all(),
                ];
            })
            ->all();
    }
}
