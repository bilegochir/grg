<?php

namespace App\Http\Controllers;

use App\Actions\CreateVisaCaseAction;
use App\Actions\UpdateVisaCaseStageAction;
use App\Enums\ApplicantNotificationEvent;
use App\Enums\VisaCasePriority;
use App\Enums\VisaCaseDocumentStatus;
use App\Http\Requests\StoreVisaCaseRequest;
use App\Http\Requests\UpdateVisaCaseStageRequest;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseGroup;
use App\Models\VisaFormTemplate;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use App\Support\VisaCaseDocumentUrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisaCaseController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'visa_type_id' => ['nullable', 'integer', 'exists:visa_types,id'],
            'priority' => ['nullable', 'string', 'max:255'],
        ]);

        $cases = $this->workspace()->scopeCases(VisaCase::query(), $request->user())
            ->with([
                'applicant:id,first_name,last_name,email,phone',
                'branch:id,name',
                'country:id,name,slug',
                'visaType:id,name,target_country_id',
                'assignedTo:id,name',
                'currentStage:id,name,color,is_closed',
            ])
            ->withCount('notes')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('reference_code', 'like', "%{$search}%")
                        ->orWhereHas('applicant', fn (Builder $applicant) => $applicant
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('visaType', fn (Builder $visaType) => $visaType->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['country'] ?? null, function (Builder $query, string $country): void {
                $query->whereHas('country', fn (Builder $builder) => $builder->where('slug', $country));
            })
            ->when($filters['visa_type_id'] ?? null, fn (Builder $query, int $visaTypeId) => $query->where('visa_type_id', $visaTypeId))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (VisaCase $visaCase): array => $this->caseSummary($visaCase));

        return Inertia::render('Cases/Index', [
            'cases' => $cases,
            'filters' => $filters,
            'countries' => TargetCountry::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'priorities' => VisaCasePriority::options(),
            'caseMeta' => [
                'applicants' => $this->workspace()->scopeApplicants(Applicant::query(), $request->user())->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
                'visaTypes' => VisaType::query()
                    ->with('country:id,name')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'target_country_id']),
                'staff' => $this->workspace()->scopeUsers(User::query(), $request->user())->orderBy('name')->get(['id', 'name']),
                'branches' => ($this->workspace()->hasGlobalBranchAccess($request->user())
                    ? Branch::query()
                    : Branch::query()->where('id', $request->user()->branch_id))
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function store(StoreVisaCaseRequest $request, CreateVisaCaseAction $createVisaCase): RedirectResponse
    {
        $applicant = Applicant::query()->findOrFail($request->integer('applicant_id'));
        $this->workspace()->assertApplicantAccess($request->user(), $applicant);

        $branchId = $this->workspace()->normalizeBranchId($request->user(), $request->integer('branch_id') ?: null);

        if ($request->filled('assigned_to_user_id')) {
            $this->workspace()->assertCanAssign($request->user());
            $this->workspace()->assertAssignableUser($request->user(), $request->integer('assigned_to_user_id'), $branchId);
        }

        $visaCase = $createVisaCase->execute($applicant, [
            ...$request->validated(),
            'branch_id' => $branchId,
        ], $request->user());

        return to_route('cases.show', $visaCase)->with('success', 'Case created.');
    }

    public function show(VisaCase $case, VisaCaseDocumentUrlGenerator $documentUrlGenerator): Response
    {
        $this->workspace()->assertCaseAccess(request()->user(), $case);

        $case->load([
            'applicant:id,first_name,last_name,email,phone',
            'branch:id,name',
            'country:id,name,slug',
            'visaType:id,name,target_country_id',
            'visaType.documentTemplates:id,visa_type_id,name,slug,description,category,client_instructions,agent_guidance,sample_hint,accepted_file_types,max_files,max_file_size_mb,due_days,is_repeatable,position,is_required,tracks_expiry',
            'visaType.workflowStages:id,visa_type_id,name,slug,position,color,is_default,is_closed',
            'assignedTo:id,name',
            'currentStage:id,name,color,is_closed',
            'documents.latestVersion',
            'documents.versions.uploader:id,name',
            'tasks.stage:id,name',
            'tasks.assignedTo:id,name',
            'appointments.assignedTo:id,name',
            'invoices.payments',
            'notes.author:id,name',
            'messages.sender:id,name',
            'activities.causer:id,name',
            'stageHistories.fromStage:id,name',
            'stageHistories.toStage:id,name,color',
            'stageHistories.user:id,name',
            'group',
            'groupMembers.applicant:id,first_name,last_name',
            'groupMembers.visaType:id,name',
        ]);

        // PDF form templates for this visa type
        $formTemplates = VisaFormTemplate::query()
            ->where('visa_type_id', $case->visa_type_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'description']);

        $memberCaseOptions = $this->workspace()->scopeCases(VisaCase::query(), request()->user())
            ->with(['applicant:id,first_name,last_name', 'visaType:id,name', 'group:id,name'])
            ->whereKeyNot($case->id)
            ->when($case->group, function ($query) use ($case) {
                $query->where(function ($inner) use ($case) {
                    $inner
                        ->whereNull('visa_case_group_id')
                        ->orWhere('visa_case_group_id', '!=', $case->group->id);
                });
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (VisaCase $candidate): array => [
                'id' => $candidate->id,
                'reference_code' => $candidate->reference_code,
                'applicant_name' => $candidate->applicant->full_name,
                'visa_type' => $candidate->visaType->name,
                'group_name' => $candidate->group?->name,
            ])->values();

        return Inertia::render('Cases/Show', [
            'case' => [
                ...$this->caseSummary($case),
                'expected_submission_at' => $case->expected_submission_at?->toDateString(),
                'expected_decision_at' => $case->expected_decision_at?->toDateString(),
                'closed_at' => $case->closed_at?->toDateTimeString(),
                'applicant' => [
                    'id' => $case->applicant->id,
                    'name' => $case->applicant->full_name,
                    'email' => $case->applicant->email,
                    'phone' => $case->applicant->phone,
                ],
                'workflow' => $case->visaType->workflowStages->map(fn (VisaWorkflowStage $stage): array => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'color' => $stage->color,
                    'is_closed' => $stage->is_closed,
                    'is_current' => $stage->id === $case->current_stage_id,
                ])->values(),
                'documents' => $case->documents->map(function (VisaCaseDocument $document) use ($documentUrlGenerator): array {
                    return [
                        'id' => $document->id,
                        'name' => $document->name,
                        'description' => $document->description,
                        'category' => $document->category,
                        'client_instructions' => $document->client_instructions,
                        'agent_guidance' => $document->agent_guidance,
                        'sample_hint' => $document->sample_hint,
                        'accepted_file_types' => $document->accepted_file_types ?? [],
                        'max_files' => $document->max_files,
                        'max_file_size_mb' => $document->max_file_size_mb,
                        'due_days' => $document->due_days,
                        'is_repeatable' => $document->is_repeatable,
                        'status' => [
                            'value' => $document->status->value,
                            'label' => $document->status->label(),
                            'color' => $document->status->color(),
                        ],
                        'is_required' => $document->is_required,
                        'tracks_expiry' => $document->tracks_expiry,
                        'expiry_date' => $document->expiry_date?->toDateString(),
                        'rejection_reason' => $document->rejection_reason,
                        'latest_version' => $document->latestVersion ? [
                            'id' => $document->latestVersion->id,
                            'original_name' => $document->latestVersion->original_name,
                            'size' => $document->latestVersion->size,
                            'version_number' => $document->latestVersion->version_number,
                            'download_url' => $documentUrlGenerator->temporaryDownloadUrl($document->latestVersion),
                        ] : null,
                        'versions' => $document->versions->map(fn ($version): array => [
                            'id' => $version->id,
                            'original_name' => $version->original_name,
                            'version_number' => $version->version_number,
                            'uploaded_by' => $version->uploader?->name ?? 'System',
                            'created_at' => $version->created_at?->toDateTimeString(),
                            'download_url' => $documentUrlGenerator->temporaryDownloadUrl($version),
                        ])->values(),
                    ];
                })->values(),
                'tasks' => $case->tasks->map(fn ($task): array => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'status' => $task->status,
                    'stage_name' => $task->stage?->name,
                    'assigned_to_id' => $task->assigned_to_user_id,
                    'assigned_to' => $task->assignedTo?->name,
                    'due_at' => $task->due_at?->toDateString(),
                    'completed_at' => $task->completed_at?->toDateTimeString(),
                    'is_required' => $task->is_required,
                    'is_client_visible' => $task->is_client_visible,
                ])->values(),
                'document_statuses' => VisaCaseDocumentStatus::options(),
                'internal_notes' => $case->notes
                    ->where('is_client_visible', false)
                    ->map(fn ($note): array => [
                        'id' => $note->id,
                        'body' => $note->body,
                        'author' => $note->author?->name ?? 'System',
                        'created_at' => $note->created_at?->toDateTimeString(),
                    ])->values(),
                'client_notes' => $case->notes
                    ->where('is_client_visible', true)
                    ->map(fn ($note): array => [
                        'id' => $note->id,
                        'body' => $note->body,
                        'author' => $note->author?->name ?? 'System',
                        'created_at' => $note->created_at?->toDateTimeString(),
                    ])->values(),
                'messages' => $case->messages->map(fn ($message): array => [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'direction' => $message->direction,
                    'channel' => $message->channel,
                    'notification_event' => $message->notification_event,
                    'subject' => $message->subject,
                    'body' => $message->body,
                    'sender_name' => $message->sender?->name ?? ($message->sender_type === 'applicant' ? $case->applicant->full_name : 'System'),
                    'sent_at' => $message->sent_at?->toDateTimeString() ?? $message->created_at?->toDateTimeString(),
                ])->values(),
                'appointments' => $case->appointments->map(fn ($appointment): array => [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'appointment_type' => $appointment->appointment_type,
                    'status' => $appointment->status,
                    'location' => $appointment->location,
                    'meeting_link' => $appointment->meeting_link,
                    'starts_at' => $appointment->starts_at?->toDateTimeString(),
                    'ends_at' => $appointment->ends_at?->toDateTimeString(),
                    'notes' => $appointment->notes,
                    'assigned_to' => $appointment->assignedTo?->name,
                    'reminder_sent_at' => $appointment->reminder_sent_at?->toDateTimeString(),
                ])->values(),
                'invoices' => $case->invoices->map(fn ($invoice): array => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'status' => $invoice->status,
                    'currency' => $invoice->currency,
                    'line_items' => $invoice->line_items ?? [],
                    'total' => number_format((float) $invoice->total, 2, '.', ''),
                    'balance_due' => number_format((float) $invoice->balance_due, 2, '.', ''),
                    'issued_at' => $invoice->issued_at?->toDateString(),
                    'due_at' => $invoice->due_at?->toDateString(),
                    'client_message' => $invoice->client_message,
                    'notes' => $invoice->notes,
                    'reminder_sent_at' => $invoice->reminder_sent_at?->toDateTimeString(),
                    'payments' => $invoice->payments->map(fn ($payment): array => [
                        'id' => $payment->id,
                        'amount' => number_format((float) $payment->amount, 2, '.', ''),
                        'method' => $payment->method,
                        'reference' => $payment->reference,
                        'paid_at' => $payment->paid_at?->toDateTimeString(),
                    ])->values(),
                ])->values(),
                'notification_events' => ApplicantNotificationEvent::options(),
                'activities' => $case->activities->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name ?? 'System',
                    'created_at' => $activity->created_at?->toDateTimeString(),
                ])->values(),
                'stage_history' => $case->stageHistories->map(fn ($history): array => [
                    'id' => $history->id,
                    'from_stage' => $history->fromStage?->name ?? 'Created',
                    'to_stage' => $history->toStage->name,
                    'changed_by' => $history->user?->name ?? 'System',
                    'changed_at' => $history->changed_at?->toDateTimeString(),
                ])->values(),
                'appointment_statuses' => [
                    'scheduled',
                    'completed',
                    'rescheduled',
                    'cancelled',
                ],
                'appointment_types' => [
                    'consultation',
                    'embassy_interview',
                    'biometrics',
                    'document_dropoff',
                    'follow_up',
                ],
                'staff_options' => $this->workspace()->scopeUsers(User::query(), request()->user())->orderBy('name')->get(['id', 'name']),
                'invoice_statuses' => [
                    'draft',
                    'sent',
                    'partially_paid',
                    'paid',
                    'overdue',
                    'void',
                ],
                'payment_methods' => [
                    'bank_transfer',
                    'cash',
                    'card',
                    'online_gateway',
                ],
                'task_statuses' => [
                    'pending',
                    'in_progress',
                    'completed',
                    'skipped',
                ],
                'group' => $case->group ? [
                    'id'   => $case->group->id,
                    'name' => $case->group->name,
                ] : null,
                'group_members' => $case->groupMembers
                    ->where('id', '!=', $case->id)
                    ->map(fn (VisaCase $member): array => [
                        'id'             => $member->id,
                        'reference_code' => $member->reference_code,
                        'applicant_name' => $member->applicant->full_name,
                        'visa_type'      => $member->visaType->name,
                        'is_group_primary' => $member->is_group_primary,
                    ])->values(),
                'member_case_options' => $memberCaseOptions,
                'form_templates' => $formTemplates->map(fn ($t): array => [
                    'id'          => $t->id,
                    'name'        => $t->name,
                    'description' => $t->description,
                ])->values(),
            ],
        ]);
    }

    public function updateStage(
        UpdateVisaCaseStageRequest $request,
        VisaCase $case,
        UpdateVisaCaseStageAction $updateVisaCaseStage,
    ): RedirectResponse {
        $this->workspace()->assertCaseAccess($request->user(), $case);

        $stage = VisaWorkflowStage::query()->findOrFail($request->integer('stage_id'));

        $updateVisaCaseStage->execute($case, $stage, $request->user());

        return back()->with('success', 'Case stage updated.');
    }

    private function caseSummary(VisaCase $visaCase): array
    {
        return [
            'id' => $visaCase->id,
            'reference_code' => $visaCase->reference_code,
            'priority' => [
                'value' => $visaCase->priority->value,
                'label' => $visaCase->priority->label(),
                'color' => $visaCase->priority->color(),
            ],
            'country' => [
                'name' => $visaCase->country->name,
                'slug' => $visaCase->country->slug,
            ],
            'branch' => $visaCase->branch?->name,
            'visa_type' => $visaCase->visaType->name,
            'stage' => $visaCase->currentStage ? [
                'id' => $visaCase->currentStage->id,
                'name' => $visaCase->currentStage->name,
                'color' => $visaCase->currentStage->color,
                'is_closed' => $visaCase->currentStage->is_closed,
            ] : null,
            'assigned_to' => $visaCase->assignedTo?->name,
            'applicant_name' => $visaCase->applicant->full_name,
            'notes_count' => $visaCase->notes_count ?? null,
        ];
    }
}
