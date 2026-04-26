<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreVisaRequirementTemplateRequest;
use App\Models\VisaRequirementTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisaRequirementLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        abort_if($request->user() === null || ! $request->user()->canManageWorkflowSettings(), 403);

        return Inertia::render('settings/VisaRequirements', [
            'templates' => $this->templates(),
        ]);
    }

    public function store(StoreVisaRequirementTemplateRequest $request): RedirectResponse
    {
        abort_if(! $request->user()?->canManageWorkflowSettings(), 403);

        $template = $this->storeTemplate($request->validated());

        return to_route('settings.visa-requirements.index')
            ->with('success', "{$template->country_name} {$template->visa_type} requirements saved.");
    }

    public function markReviewed(Request $request, VisaRequirementTemplate $visaRequirementTemplate): RedirectResponse
    {
        abort_unless($request->user()?->canManageWorkflowSettings(), 403);

        $visaRequirementTemplate->update([
            'source_checked_at' => now()->toDateString(),
        ]);

        return to_route('settings.visa-requirements.index')
            ->with('success', "{$visaRequirementTemplate->country_name} {$visaRequirementTemplate->visa_type} marked as reviewed.");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function templates(): array
    {
        return VisaRequirementTemplate::query()
            ->with('items')
            ->orderByRaw(
                "case when region = 'Australia' then 0 when region = 'Europe' then 1 when region = 'Asia' then 2 else 3 end",
            )
            ->orderBy('country_name')
            ->orderBy('visa_type')
            ->get()
            ->map(fn (VisaRequirementTemplate $template): array => [
                'id' => $template->id,
                'region' => $template->region,
                'country_name' => $template->country_name,
                'visa_type' => $template->visa_type,
                'visa_code' => $template->visa_code,
                'requires_institution_name' => $template->requires_institution_name,
                'label' => $template->label,
                'description' => $template->description,
                'source_url' => $template->source_url,
                'source_checked_at' => $template->source_checked_at?->toDateString(),
                'processing_time_summary' => $template->processing_time_summary,
                'fee_summary' => $template->fee_summary,
                'stay_summary' => $template->stay_summary,
                'is_active' => $template->is_active,
                'items' => $template->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'category' => $item->category,
                    'label' => $item->label,
                    'help_text' => $item->help_text,
                    'is_required' => $item->is_required,
                ])->all(),
            ])
            ->all();
    }

    /**
     * @param  array{
     *     country_name: string,
     *     description: null|string,
     *     fee_summary: null|string,
     *     processing_time_summary: null|string,
     *     region: string,
     *     requires_institution_name: bool,
     *     requirements: list<array{category: null|string, help_text: null|string, is_required: bool, label: string}>,
     *     source_checked_at: null|string,
     *     source_url: null|string,
     *     stay_summary: null|string,
     *     visa_code: null|string,
     *     visa_type: string
     * }  $validated
     */
    private function storeTemplate(array $validated): VisaRequirementTemplate
    {
        $template = VisaRequirementTemplate::query()->updateOrCreate(
            [
                'country_name' => trim($validated['country_name']),
                'visa_type' => trim($validated['visa_type']),
            ],
            [
                'region' => $validated['region'],
                'visa_code' => filled($validated['visa_code']) ? trim($validated['visa_code']) : null,
                'requires_institution_name' => $validated['requires_institution_name'],
                'label' => trim($validated['country_name']).' '.trim($validated['visa_type']).' checklist',
                'description' => filled($validated['description']) ? trim($validated['description']) : null,
                'source_url' => filled($validated['source_url']) ? trim($validated['source_url']) : null,
                'source_checked_at' => $validated['source_checked_at'] ?: now()->toDateString(),
                'processing_time_summary' => filled($validated['processing_time_summary']) ? trim($validated['processing_time_summary']) : null,
                'fee_summary' => filled($validated['fee_summary']) ? trim($validated['fee_summary']) : null,
                'stay_summary' => filled($validated['stay_summary']) ? trim($validated['stay_summary']) : null,
                'is_active' => true,
            ],
        );

        $this->syncTemplateItems($template, $validated['requirements']);

        return $template->load('items');
    }

    /**
     * @param  list<array{category: null|string, help_text: null|string, is_required: bool, label: string}>  $requirements
     */
    private function syncTemplateItems(VisaRequirementTemplate $template, array $requirements): void
    {
        foreach ($requirements as $index => $requirement) {
            $template->items()->updateOrCreate(
                ['sort_order' => $index + 1],
                [
                    'category' => filled($requirement['category']) ? trim($requirement['category']) : null,
                    'label' => trim($requirement['label']),
                    'help_text' => filled($requirement['help_text']) ? trim($requirement['help_text']) : null,
                    'is_required' => $requirement['is_required'],
                ],
            );
        }

        $template->items()
            ->where('sort_order', '>', count($requirements))
            ->delete();
    }
}
