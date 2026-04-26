<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreVisaCaseStatusTemplateRequest;
use App\Support\VisaCaseStatusTemplateResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VisaCaseStatusTemplateController extends Controller
{
    public function __construct(private VisaCaseStatusTemplateResolver $visaCaseStatusTemplateResolver) {}

    public function index(): Response
    {
        $agency = request()->user()?->agency;
        abort_if($agency === null || ! request()->user()?->canManageWorkflowSettings(), 403);

        return Inertia::render('settings/VisaCaseStatusTemplates', [
            'templates' => $this->visaCaseStatusTemplateResolver->templates($agency)
                ->map(fn ($template): array => [
                    'status_key' => $template->status_key,
                    'label' => $template->label,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(StoreVisaCaseStatusTemplateRequest $request): RedirectResponse
    {
        $agency = $request->user()?->agency;
        abort_if($agency === null || ! $request->user()?->canManageWorkflowSettings(), 403);

        foreach ($request->validated('templates') as $index => $template) {
            $agency->visaCaseStatusTemplates()->updateOrCreate(
                ['status_key' => $template['status_key']],
                [
                    'label' => trim($template['label']),
                    'sort_order' => $index + 1,
                ],
            );
        }

        return to_route('settings.visa-statuses.index')->with('success', 'Visa statuses updated.');
    }
}
