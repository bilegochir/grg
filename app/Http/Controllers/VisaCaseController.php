<?php

namespace App\Http\Controllers;

use App\Enums\VisaCaseStatus;
use App\Enums\VisaRequirementStatus;
use App\Http\Requests\StoreVisaCaseRequest;
use App\Http\Requests\UpdateVisaCaseRequest;
use App\Models\Attachment;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaRequirementTemplate;
use App\Notifications\VisaCaseAssignedNotification;
use App\Support\ActivityTimeline;
use App\Support\CrmActivityLogger;
use App\Support\DefaultVisaCaseTaskCreator;
use App\Support\TaskStatusTemplateResolver;
use App\Support\VisaCaseStatusTemplateResolver;
use App\Support\VisaRequirementChecklistSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VisaCaseController extends Controller
{
    public function index(Request $request, VisaCaseStatusTemplateResolver $visaCaseStatusTemplateResolver): Response
    {
        $this->authorize('viewAny', VisaCase::class);

        $agency = $request->user()->agency;
        abort_if($agency === null, 403);
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');
        $country = trim((string) $request->input('country', ''));

        $activeCaseStatuses = array_map(
            static fn (VisaCaseStatus $status): string => $status->value,
            VisaCaseStatus::active(),
        );
        $statusLabels = $visaCaseStatusTemplateResolver->labelsByStatus($agency);

        return Inertia::render('visa-cases/Index', [
            'visaCases' => VisaCase::query()
                ->forAgency($agency)
                ->with(['client:id,full_name', 'assignee:id,name'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($searchQuery) use ($search): void {
                        $searchQuery
                            ->where('reference_code', 'like', "%{$search}%")
                            ->orWhere('visa_type', 'like', "%{$search}%")
                            ->orWhere('institution_name', 'like', "%{$search}%")
                            ->orWhere('destination_country', 'like', "%{$search}%")
                            ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('full_name', 'like', "%{$search}%"))
                            ->orWhereHas('assignee', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($country !== '', fn ($query) => $query->where('destination_country', $country))
                ->orderByRaw(
                    "case status
                        when 'documents_pending' then 0
                        when 'ready_to_file' then 1
                        when 'intake' then 2
                        when 'submitted' then 3
                        when 'approved' then 4
                        when 'rejected' then 5
                        else 6
                    end"
                )
                ->latest('created_at')
                ->get()
                ->map(fn (VisaCase $visaCase): array => [
                    'id' => $visaCase->id,
                    'reference_code' => $visaCase->reference_code,
                    'visa_type' => $visaCase->visa_type,
                    'destination_country' => $visaCase->destination_country,
                    'institution_name' => $visaCase->institution_name,
                    'status' => $visaCase->status->value,
                    'status_label' => $statusLabels[$visaCase->status->value] ?? $visaCase->status->label(),
                    'client_name' => $visaCase->client?->full_name,
                    'assignee_name' => $visaCase->assignee?->name,
                    'submitted_at' => $visaCase->submitted_at?->toDateString(),
                    'decision_at' => $visaCase->decision_at?->toDateString(),
                ])
                ->values(),
            'clients' => $agency->clients()
                ->orderBy('full_name')
                ->get(['id', 'full_name'])
                ->map(fn ($client): array => ['id' => $client->id, 'full_name' => $client->full_name])
                ->values(),
            'users' => $agency->users()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($user): array => ['id' => $user->id, 'name' => $user->name])
                ->values(),
            ...$this->requirementOptions(),
            'statusOptions' => $visaCaseStatusTemplateResolver->options($agency),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'country' => $country,
            ],
            'stats' => [
                'total' => VisaCase::query()->forAgency($agency)->count(),
                'active' => VisaCase::query()->forAgency($agency)->whereIn('status', $activeCaseStatuses)->count(),
                'submitted' => VisaCase::query()->forAgency($agency)->where('status', VisaCaseStatus::Submitted)->count(),
            ],
        ]);
    }

    public function show(
        Request $request,
        VisaCase $visaCase,
        VisaCaseStatusTemplateResolver $visaCaseStatusTemplateResolver,
        TaskStatusTemplateResolver $taskStatusTemplateResolver,
    ): Response {
        $this->authorize('view', $visaCase);

        $visaCase->load([
            'client:id,full_name',
            'assignee:id,name',
            'tasks.assignee:id,name',
            'crmNotes.author:id,name',
            'attachments.uploader:id,name',
            'requirements.attachments.uploader:id,name',
            'requirements.templateItem.template',
        ]);

        $requirementTemplate = $visaCase->requirements->first()?->templateItem?->template;
        $agency = $request->user()?->agency;
        abort_if($agency === null, 403);
        $statusLabels = $visaCaseStatusTemplateResolver->labelsByStatus($agency);
        $taskStatusLabels = $taskStatusTemplateResolver->labelsByStatus($agency);

        return Inertia::render('visa-cases/Show', [
            'visaCase' => [
                'id' => $visaCase->id,
                'client_id' => $visaCase->client_id,
                'assigned_user_id' => $visaCase->assigned_user_id,
                'reference_code' => $visaCase->reference_code,
                'visa_type' => $visaCase->visa_type,
                'destination_country' => $visaCase->destination_country,
                'institution_name' => $visaCase->institution_name,
                'status' => $visaCase->status->value,
                'status_label' => $statusLabels[$visaCase->status->value] ?? $visaCase->status->label(),
                'client_name' => $visaCase->client?->full_name,
                'assignee_name' => $visaCase->assignee?->name,
                'submitted_at' => $visaCase->submitted_at?->toIso8601String(),
                'decision_at' => $visaCase->decision_at?->toIso8601String(),
                'notes' => $visaCase->crmNotes->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'author_name' => $note->author?->name,
                    'created_at' => $note->created_at?->toIso8601String(),
                ])->values(),
                'attachments' => $visaCase->attachments->map(fn (Attachment $attachment): array => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->human_size,
                    'uploaded_by' => $attachment->uploader?->name,
                    'created_at' => $attachment->created_at?->toIso8601String(),
                    'download_url' => route('attachments.show', $attachment),
                ])->values(),
            ],
            'clients' => $request->user()->agency?->clients()
                ->orderBy('full_name')
                ->get(['id', 'full_name'])
                ->map(fn ($client): array => ['id' => $client->id, 'full_name' => $client->full_name])
                ->values(),
            'users' => $request->user()->agency?->users()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($user): array => ['id' => $user->id, 'name' => $user->name])
                ->values(),
            ...$this->requirementOptions(),
            'statusOptions' => $visaCaseStatusTemplateResolver->options($agency),
            'requirementStatusOptions' => VisaRequirementStatus::options(),
            'requirementTemplate' => $requirementTemplate === null ? null : [
                'label' => $requirementTemplate->label,
                'region' => $requirementTemplate->region,
                'country_name' => $requirementTemplate->country_name,
                'visa_type' => $requirementTemplate->visa_type,
                'description' => $requirementTemplate->description,
                'source_url' => $requirementTemplate->source_url,
                'source_checked_at' => $requirementTemplate->source_checked_at?->toDateString(),
                'processing_time_summary' => $requirementTemplate->processing_time_summary,
                'fee_summary' => $requirementTemplate->fee_summary,
                'stay_summary' => $requirementTemplate->stay_summary,
            ],
            'requirements' => $visaCase->requirements->map(fn ($requirement): array => [
                'id' => $requirement->id,
                'category' => $requirement->category,
                'label' => $requirement->label,
                'help_text' => $requirement->help_text,
                'is_required' => $requirement->is_required,
                'status' => $requirement->status->value,
                'status_label' => $requirement->status->label(),
                'due_at' => $requirement->due_at?->toDateString(),
                'requested_at' => $requirement->requested_at?->toIso8601String(),
                'received_at' => $requirement->received_at?->toIso8601String(),
                'reviewed_at' => $requirement->reviewed_at?->toIso8601String(),
                'review_notes' => $requirement->review_notes,
                'is_completed' => $requirement->is_completed,
                'completed_at' => $requirement->completed_at?->toIso8601String(),
                'attachments' => $requirement->attachments->map(fn (Attachment $attachment): array => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->human_size,
                    'uploaded_by' => $attachment->uploader?->name,
                    'created_at' => $attachment->created_at?->toIso8601String(),
                    'download_url' => route('attachments.show', $attachment),
                ])->values(),
            ])
                ->values(),
            'tasks' => $visaCase->tasks->map(fn ($task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'status_label' => $taskStatusLabels[$task->status->value] ?? $task->status->label(),
                'priority_label' => $task->priority->label(),
                'assignee_name' => $task->assignee?->name,
                'due_at' => $task->due_at?->toIso8601String(),
            ])->values(),
            'timeline' => ActivityTimeline::forVisaCase($visaCase)->values(),
        ]);
    }

    public function store(
        StoreVisaCaseRequest $request,
        DefaultVisaCaseTaskCreator $defaultVisaCaseTaskCreator,
        VisaRequirementChecklistSynchronizer $checklistSynchronizer,
    ): RedirectResponse {
        $this->authorize('create', VisaCase::class);

        $agency = $request->user()->agency;
        abort_if($agency === null, 403);

        $visaCase = DB::transaction(function () use ($agency, $checklistSynchronizer, $defaultVisaCaseTaskCreator, $request): VisaCase {
            $visaCase = $agency->visaCases()->create($this->validatedVisaCaseData($request));

            $checklistSynchronizer->sync($visaCase);
            $defaultVisaCaseTaskCreator->createForCurrentStatus($visaCase, $request->user());

            return $visaCase;
        });

        $this->notifyAssignee($visaCase, null, $request->user());

        return to_route('visa-cases.show', $visaCase)->with('success', 'Visa case created successfully.');
    }

    public function update(
        UpdateVisaCaseRequest $request,
        VisaCase $visaCase,
        DefaultVisaCaseTaskCreator $defaultVisaCaseTaskCreator,
        CrmActivityLogger $crmActivityLogger,
        VisaRequirementChecklistSynchronizer $checklistSynchronizer,
    ): RedirectResponse {
        $this->authorize('update', $visaCase);

        $previousStatus = $visaCase->status;
        $previousAssignedUserId = $visaCase->assigned_user_id;
        $originalAttributes = $visaCase->getRawOriginal();

        $visaCase->update($this->validatedVisaCaseData($request));
        $checklistSynchronizer->sync($visaCase);
        $crmActivityLogger->logVisaCaseUpdated($visaCase, $originalAttributes, $request->user());

        if ($visaCase->status !== $previousStatus) {
            $defaultVisaCaseTaskCreator->createForCurrentStatus($visaCase, $request->user());
        }

        $this->notifyAssignee($visaCase, $previousAssignedUserId, $request->user());

        return to_route('visa-cases.show', $visaCase)->with('success', 'Visa case updated successfully.');
    }

    /**
     * @return array{
     *     requirementCountries: list<string>,
     *     institutionRequirements: array<string, bool>,
     *     visaTypesByCountry: array<string, list<string>>
     * }
     */
    private function requirementOptions(): array
    {
        $requirementTemplates = VisaRequirementTemplate::query()
            ->active()
            ->orderBy('country_name')
            ->orderBy('visa_type')
            ->get(['country_name', 'visa_type', 'requires_institution_name']);

        return [
            'requirementCountries' => $requirementTemplates
                ->pluck('country_name')
                ->unique()
                ->values()
                ->all(),
            'visaTypesByCountry' => $requirementTemplates
                ->groupBy('country_name')
                ->map(
                    fn ($templates): array => $templates
                        ->pluck('visa_type')
                        ->unique()
                        ->values()
                        ->all(),
                )
                ->all(),
            'institutionRequirements' => $requirementTemplates
                ->mapWithKeys(
                    fn (VisaRequirementTemplate $template): array => [
                        $this->institutionRequirementKey($template->country_name, $template->visa_type) => $template->requires_institution_name,
                    ],
                )
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedVisaCaseData(StoreVisaCaseRequest|UpdateVisaCaseRequest $request): array
    {
        $validated = $request->validated();

        if (! VisaRequirementTemplate::requiresInstitutionName(
            $validated['destination_country'] ?? null,
            $validated['visa_type'] ?? null,
        )) {
            $validated['institution_name'] = null;
        }

        return $validated;
    }

    private function institutionRequirementKey(string $countryName, string $visaType): string
    {
        return "{$countryName}::{$visaType}";
    }

    private function notifyAssignee(VisaCase $visaCase, ?int $previousAssignedUserId, User $actor): void
    {
        if (
            $visaCase->assigned_user_id === null
            || $visaCase->assigned_user_id === $previousAssignedUserId
        ) {
            return;
        }

        $visaCase->assignee?->notify(new VisaCaseAssignedNotification($visaCase, $actor));
    }
}
