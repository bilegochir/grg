<?php

namespace App\Http\Controllers;

use App\Actions\RecordActivityAction;
use App\Actions\UploadVisaCaseDocumentVersionAction;
use App\Models\Applicant;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseTask;
use App\Models\VisaWorkflowStage;
use App\Support\VisaCaseDocumentUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantPortalController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $applicant = $this->portalApplicant($request);
        $applicant->load([
            'visaCases.country:id,name',
            'visaCases.visaType:id,name',
            'visaCases.visaType.workflowStages:id,visa_type_id,name,slug,position',
            'visaCases.currentStage:id,name',
            'visaCases.documents.latestVersion',
            'visaCases.tasks',
            'visaCases.appointments.assignedTo:id,name',
            'visaCases.invoices.payments',
            'visaCases.messages',
        ]);

        $casePayloads = $applicant->visaCases
            ->map(fn (VisaCase $visaCase): array => $this->portalCaseSummary($visaCase))
            ->values();

        return Inertia::render('Portal/Dashboard', [
            'business' => $this->businessPayload(),
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->full_name,
                'email' => $applicant->email,
            ],
            'summary' => [
                'cases_count' => $casePayloads->count(),
                'open_tasks_count' => $casePayloads->sum('open_tasks_count'),
                'documents_waiting_count' => $casePayloads->sum('documents_waiting_count'),
                'balance_due' => number_format((float) $applicant->visaCases->sum(fn (VisaCase $visaCase) => $visaCase->invoices->sum('balance_due')), 2, '.', ''),
            ],
            'cases' => $casePayloads,
        ]);
    }

    public function showCase(
        Request $request,
        VisaCase $case,
        VisaCaseDocumentUrlGenerator $documentUrlGenerator,
    ): Response {
        $applicant = $this->portalApplicant($request);
        abort_unless($case->applicant_id === $applicant->id, 404);

        $case->load([
            'country:id,name',
            'visaType:id,name',
            'visaType.workflowStages:id,visa_type_id,name,slug,position',
            'currentStage:id,name',
            'documents.latestVersion',
            'documents.versions',
            'tasks.stage:id,name',
            'tasks.assignedTo:id,name',
            'appointments.assignedTo:id,name',
            'invoices.payments',
            'messages',
            'stageHistories.fromStage:id,name,slug,position',
            'stageHistories.toStage:id,name,slug,position',
            'stageHistories.user:id,name',
            'activities.causer:id,name',
        ]);

        return Inertia::render('Portal/Case', [
            'business' => $this->businessPayload(),
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->full_name,
                'email' => $applicant->email,
            ],
            'case' => $this->portalCaseDetail($case, $documentUrlGenerator),
        ]);
    }

    public function uploadDocument(
        Request $request,
        VisaCase $case,
        VisaCaseDocument $document,
        UploadVisaCaseDocumentVersionAction $uploadDocumentVersion,
    ): RedirectResponse {
        $applicant = $this->portalApplicant($request);
        abort_unless($case->applicant_id === $applicant->id && $document->visa_case_id === $case->id, 404);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $uploadDocumentVersion->execute($document, $request->file('file'));

        return back()->with('success', 'Document uploaded. Your team will review it shortly.');
    }

    public function storeMessage(
        Request $request,
        VisaCase $case,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $applicant = $this->portalApplicant($request);
        abort_unless($case->applicant_id === $applicant->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $message = $case->messages()->create([
            'sender_type' => 'applicant',
            'direction' => 'inbound',
            'channel' => 'portal',
            'body' => $data['body'],
            'metadata' => ['source' => 'portal'],
            'sent_at' => now(),
        ]);

        $recordActivity->execute(
            $case,
            'visa_case.portal_message_received',
            'Applicant sent a portal message.',
            null,
            ['message_id' => $message->id],
        );

        return back()->with('success', 'Your message has been sent to your visa team.');
    }

    private function portalApplicant(Request $request): Applicant
    {
        return Applicant::query()->findOrFail($request->session()->get('portal_applicant_id'));
    }

    private function businessPayload(): array
    {
        $business = BusinessSetting::current();

        return [
            'name' => $business->business_name,
            'logo_url' => $business->logoUrl(),
            'email' => $business->contact_email,
            'phone' => $business->contact_phone,
        ];
    }

    private function portalCaseSummary(VisaCase $visaCase): array
    {
        $documentsVerified = $visaCase->documents->filter(fn (VisaCaseDocument $document) => $document->status->value === 'verified')->count();
        $documentsWaiting = $visaCase->documents->filter(fn (VisaCaseDocument $document) => in_array($document->status->value, ['pending', 'rejected'], true))->count();
        $visibleTasks = $visaCase->tasks->where('is_client_visible', true);
        $openTasksCount = $visibleTasks->where('status', '!=', 'completed')->count();
        $workflowStages = $visaCase->visaType->workflowStages->sortBy('position')->values();
        $currentStagePosition = $workflowStages->firstWhere('id', $visaCase->current_stage_id)?->position ?? 1;
        $progressPercent = $workflowStages->isNotEmpty()
            ? (int) round(($currentStagePosition / max($workflowStages->count(), 1)) * 100)
            : 0;

        return [
            'id' => $visaCase->id,
            'reference_code' => $visaCase->reference_code,
            'country' => $visaCase->country->name,
            'visa_type' => $visaCase->visaType->name,
            'stage' => $visaCase->currentStage?->name,
            'stage_copy' => $this->stageCopy($visaCase->currentStage?->slug),
            'progress_percent' => $progressPercent,
            'documents_verified' => $documentsVerified,
            'documents_total' => $visaCase->documents->count(),
            'documents_waiting_count' => $documentsWaiting,
            'open_tasks_count' => $openTasksCount,
            'appointments_count' => $visaCase->appointments->count(),
            'balance_due' => number_format((float) $visaCase->invoices->sum('balance_due'), 2, '.', ''),
            'latest_message_at' => optional($visaCase->messages->first())->sent_at?->diffForHumans(),
            'next_step' => $this->nextStepCopy($visaCase, $documentsWaiting, $openTasksCount),
        ];
    }

    private function portalCaseDetail(VisaCase $case, VisaCaseDocumentUrlGenerator $documentUrlGenerator): array
    {
        $workflowStages = $case->visaType->workflowStages->sortBy('position')->values();
        $currentStagePosition = $workflowStages->firstWhere('id', $case->current_stage_id)?->position ?? 1;
        $visibleTasks = $case->tasks->where('is_client_visible', true)->values();
        $documentsVerified = $case->documents->filter(fn (VisaCaseDocument $document) => $document->status->value === 'verified')->count();
        $documentsWaiting = $case->documents->filter(fn (VisaCaseDocument $document) => in_array($document->status->value, ['pending', 'rejected'], true))->count();
        $nextAppointment = $case->appointments
            ->filter(fn ($appointment) => $appointment->starts_at && $appointment->starts_at->isFuture())
            ->sortBy('starts_at')
            ->first();

        return [
            'id' => $case->id,
            'reference_code' => $case->reference_code,
            'country' => $case->country->name,
            'visa_type' => $case->visaType->name,
            'stage' => $case->currentStage?->name,
            'stage_copy' => $this->stageCopy($case->currentStage?->slug),
            'next_step_copy' => $this->nextStepCopy($case, $documentsWaiting, $visibleTasks->where('status', '!=', 'completed')->count()),
            'progress_percent' => $workflowStages->isNotEmpty()
                ? (int) round(($currentStagePosition / max($workflowStages->count(), 1)) * 100)
                : 0,
            'summary' => [
                'documents_verified' => $documentsVerified,
                'documents_total' => $case->documents->count(),
                'documents_waiting_count' => $documentsWaiting,
                'open_tasks_count' => $visibleTasks->where('status', '!=', 'completed')->count(),
                'completed_tasks_count' => $visibleTasks->where('status', 'completed')->count(),
                'balance_due' => number_format((float) $case->invoices->sum('balance_due'), 2, '.', ''),
                'next_appointment_at' => $nextAppointment?->starts_at?->toDayDateTimeString(),
            ],
            'workflow' => $workflowStages->map(function (VisaWorkflowStage $stage) use ($currentStagePosition): array {
                $state = $stage->position < $currentStagePosition
                    ? 'completed'
                    : ($stage->position === $currentStagePosition ? 'current' : 'upcoming');

                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'state' => $state,
                ];
            })->values(),
            'tasks' => $visibleTasks->map(fn (VisaCaseTask $task): array => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'status' => $task->status,
                'status_label' => match ($task->status) {
                    'completed' => 'Done',
                    'in_progress' => 'In progress',
                    'skipped' => 'Not needed',
                    default => 'Coming up',
                },
                'due_at' => $task->due_at?->toFormattedDateString(),
                'is_required' => $task->is_required,
                'assigned_to' => $task->assignedTo?->name,
                'stage_name' => $task->stage?->name,
            ])->values(),
            'documents' => $case->documents->map(fn (VisaCaseDocument $document): array => [
                'id' => $document->id,
                'name' => $document->name,
                'description' => $document->description,
                'category' => $document->category,
                'client_instructions' => $document->client_instructions,
                'sample_hint' => $document->sample_hint,
                'status' => $document->status->label(),
                'status_value' => $document->status->value,
                'status_copy' => match ($document->status->value) {
                    'verified' => 'Your team reviewed this and it looks good.',
                    'uploaded' => 'Received. Your team will review it shortly.',
                    'rejected' => 'This needs one more update before it can be approved.',
                    default => 'Still needed before the case can move forward.',
                },
                'accepted_file_types' => $document->accepted_file_types ?? [],
                'max_file_size_mb' => $document->max_file_size_mb,
                'is_required' => $document->is_required,
                'tracks_expiry' => $document->tracks_expiry,
                'expiry_date' => $document->expiry_date?->toFormattedDateString(),
                'rejection_reason' => $document->rejection_reason,
                'latest_version' => $document->latestVersion ? [
                    'original_name' => $document->latestVersion->original_name,
                    'created_at' => $document->latestVersion->created_at?->toDayDateTimeString(),
                    'download_url' => $documentUrlGenerator->temporaryDownloadUrl($document->latestVersion),
                ] : null,
                'version_count' => $document->versions->count(),
            ])->values(),
            'appointments' => $case->appointments->map(fn ($appointment): array => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'appointment_type' => $appointment->appointment_type,
                'status' => $appointment->status,
                'starts_at' => $appointment->starts_at?->toDayDateTimeString(),
                'location' => $appointment->location,
                'meeting_link' => $appointment->meeting_link,
                'agent' => $appointment->assignedTo?->name,
                'notes' => $appointment->notes,
            ])->values(),
            'invoices' => $case->invoices->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'status_copy' => match ($invoice->status) {
                    'paid' => 'Paid in full',
                    'partially_paid' => 'Partially paid',
                    'overdue' => 'Payment is overdue',
                    'sent' => 'Awaiting payment',
                    default => 'Processing',
                },
                'currency' => $invoice->currency,
                'line_items' => $invoice->line_items ?? [],
                'total' => number_format((float) $invoice->total, 2, '.', ''),
                'paid_amount' => number_format((float) ((float) $invoice->total - (float) $invoice->balance_due), 2, '.', ''),
                'balance_due' => number_format((float) $invoice->balance_due, 2, '.', ''),
                'due_at' => $invoice->due_at?->toFormattedDateString(),
                'client_message' => $invoice->client_message,
                'payments' => $invoice->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                    'paid_at' => $payment->paid_at?->toDayDateTimeString(),
                    'method' => $payment->method,
                    'reference' => $payment->reference,
                ])->values(),
            ])->values(),
            'messages' => $case->messages->map(fn ($message): array => [
                'id' => $message->id,
                'direction' => $message->direction,
                'channel' => $message->channel,
                'body' => $message->body,
                'subject' => $message->subject,
                'sent_at' => $message->sent_at?->toDayDateTimeString() ?? $message->created_at?->toDayDateTimeString(),
            ])->values(),
            'timeline' => $this->portalTimeline($case),
        ];
    }

    private function portalTimeline(VisaCase $case): array
    {
        $items = collect();

        foreach ($case->stageHistories as $history) {
            $items->push([
                'type' => 'stage',
                'title' => $history->toStage?->name ? "Case moved to {$history->toStage->name}" : 'Case stage updated',
                'body' => $history->user?->name ? "Updated by {$history->user->name}." : 'Your visa team updated the next step.',
                'at' => $history->changed_at,
            ]);
        }

        foreach ($case->documents as $document) {
            if ($document->latestVersion?->created_at) {
                $items->push([
                    'type' => 'document',
                    'title' => "{$document->name} uploaded",
                    'body' => 'Your latest file is with the team for review.',
                    'at' => $document->latestVersion->created_at,
                ]);
            }
        }

        foreach ($case->invoices as $invoice) {
            foreach ($invoice->payments as $payment) {
                if ($payment->paid_at) {
                    $items->push([
                        'type' => 'payment',
                        'title' => "Payment received for {$invoice->number}",
                        'body' => "{$invoice->currency} {$payment->amount} was recorded.",
                        'at' => $payment->paid_at,
                    ]);
                }
            }
        }

        foreach ($case->appointments as $appointment) {
            if ($appointment->starts_at) {
                $items->push([
                    'type' => 'appointment',
                    'title' => $appointment->title,
                    'body' => $appointment->status === 'completed'
                        ? 'This appointment is complete.'
                        : 'This has been scheduled on your case.',
                    'at' => $appointment->starts_at,
                ]);
            }
        }

        return $items
            ->filter(fn (array $item) => $item['at'] !== null)
            ->sortByDesc('at')
            ->take(12)
            ->values()
            ->map(fn (array $item): array => [
                'type' => $item['type'],
                'title' => $item['title'],
                'body' => $item['body'],
                'at' => $item['at']?->toDayDateTimeString(),
            ])
            ->all();
    }

    private function stageCopy(?string $slug): string
    {
        return match ($slug) {
            'documents-pending' => "We're waiting on a few documents from you before we can move ahead.",
            'under-review' => "We're reviewing your documents. This usually takes 1 to 2 business days.",
            'submitted-to-embassy' => 'Your application has been submitted and is now with the embassy or visa office.',
            'decision' => 'A decision is being finalized. We will update you as soon as it lands.',
            default => 'Your case is moving forward and your team will keep you posted on the next step.',
        };
    }

    private function nextStepCopy(VisaCase $case, int $documentsWaiting, int $openTasksCount): string
    {
        if ($documentsWaiting > 0) {
            return $documentsWaiting === 1
                ? 'You have 1 document left to send before the team can move ahead.'
                : "You have {$documentsWaiting} documents left to send before the team can move ahead.";
        }

        if ($openTasksCount > 0) {
            return $openTasksCount === 1
                ? 'There is 1 checklist item for you to keep an eye on.'
                : "There are {$openTasksCount} checklist items for you to keep an eye on.";
        }

        if ($case->invoices->sum('balance_due') > 0) {
            return 'There is an outstanding balance on this case. Your team can help if you need payment instructions.';
        }

        return 'Everything needed from you is in good shape right now. Your team will reach out if the next step needs you.';
    }
}
