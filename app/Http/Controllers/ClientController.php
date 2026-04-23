<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Attachment;
use App\Models\Client;
use App\Support\ActivityTimeline;
use App\Support\ClientProfileDataNormalizer;
use App\Support\TaskStatusTemplateResolver;
use App\Support\VisaCaseStatusTemplateResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $agency = $request->user()->agency;
        abort_if($agency === null, 403);
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');

        $openTaskStatuses = array_map(
            static fn (TaskStatus $status): string => $status->value,
            TaskStatus::open(),
        );

        $clients = Client::query()
            ->forAgency($agency)
            ->with('owner:id,name')
            ->withCount([
                'visaCases',
                'tasks as open_tasks_count' => fn ($query) => $query->whereIn('status', $openTaskStatuses),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('passport_number', 'like', "%{$search}%")
                        ->orWhere('occupation', 'like', "%{$search}%")
                        ->orWhere('nationality', 'like', "%{$search}%")
                        ->orWhere('destination_country', 'like', "%{$search}%")
                        ->orWhere('lead_source', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw(
                "case status
                    when 'active' then 0
                    when 'qualified' then 1
                    when 'lead' then 2
                    else 3
                end"
            )
            ->latest('created_at')
            ->get();

        return Inertia::render('clients/Index', [
            'clients' => $clients->map(fn (Client $client): array => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'nationality' => $client->nationality,
                'destination_country' => $client->destination_country,
                'lead_source' => $client->lead_source,
                'status' => $client->status->value,
                'status_label' => $client->status->label(),
                'owner_name' => $client->owner?->name,
                'visa_cases_count' => $client->visa_cases_count,
                'open_tasks_count' => $client->open_tasks_count,
                'created_at' => $client->created_at?->toDateString(),
            ])->values(),
            'maritalStatusOptions' => $this->maritalStatusOptions(),
            'statusOptions' => ClientStatus::options(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'total' => Client::query()->forAgency($agency)->count(),
                'active' => Client::query()->forAgency($agency)->where('status', ClientStatus::Active)->count(),
                'qualified' => Client::query()->forAgency($agency)->where('status', ClientStatus::Qualified)->count(),
            ],
        ]);
    }

    public function show(
        Request $request,
        Client $client,
        VisaCaseStatusTemplateResolver $visaCaseStatusTemplateResolver,
        TaskStatusTemplateResolver $taskStatusTemplateResolver,
    ): Response {
        $this->authorize('view', $client);

        $client->load([
            'owner:id,name',
            'visaCases.assignee:id,name',
            'tasks.assignee:id,name',
            'crmNotes.author:id,name',
            'attachments.uploader:id,name',
        ]);
        $agency = $request->user()?->agency;
        abort_if($agency === null, 403);
        $statusLabels = $visaCaseStatusTemplateResolver->labelsByStatus($agency);
        $taskStatusLabels = $taskStatusTemplateResolver->labelsByStatus($agency);

        return Inertia::render('clients/Show', [
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'date_of_birth' => $client->date_of_birth?->toDateString(),
                'passport_number' => $client->passport_number,
                'passport_expiry_date' => $client->passport_expiry_date?->toDateString(),
                'marital_status' => $client->marital_status,
                'occupation' => $client->occupation,
                'nationality' => $client->nationality,
                'destination_country' => $client->destination_country,
                'lead_source' => $client->lead_source,
                'status' => $client->status->value,
                'status_label' => $client->status->label(),
                'owner_name' => $client->owner?->name,
                'current_address' => $client->current_address,
                'portal_url' => route('portal.show', $client->portal_token),
                'portal_login_url' => route('portal.login'),
                'family_members' => $client->family_members ?? [],
                'education_history' => $client->education_history ?? [],
                'work_experiences' => $client->work_experiences ?? [],
                'notes' => $client->crmNotes->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'author_name' => $note->author?->name,
                    'created_at' => $note->created_at?->toIso8601String(),
                ])->values(),
                'attachments' => $client->attachments->map(fn (Attachment $attachment): array => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->human_size,
                    'uploaded_by' => $attachment->uploader?->name,
                    'created_at' => $attachment->created_at?->toIso8601String(),
                    'download_url' => route('attachments.show', $attachment),
                ])->values(),
            ],
            'maritalStatusOptions' => $this->maritalStatusOptions(),
            'statusOptions' => ClientStatus::options(),
            'visaCases' => $client->visaCases->map(fn ($visaCase): array => [
                'id' => $visaCase->id,
                'reference_code' => $visaCase->reference_code,
                'visa_type' => $visaCase->visa_type,
                'status_label' => $statusLabels[$visaCase->status->value] ?? $visaCase->status->label(),
                'assignee_name' => $visaCase->assignee?->name,
                'show_url' => route('visa-cases.show', $visaCase),
            ])->values(),
            'tasks' => $client->tasks->map(fn ($task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'status_label' => $taskStatusLabels[$task->status->value] ?? $task->status->label(),
                'priority_label' => $task->priority->label(),
                'assignee_name' => $task->assignee?->name,
                'due_at' => $task->due_at?->toIso8601String(),
            ])->values(),
            'timeline' => ActivityTimeline::forClient($client)->values(),
        ]);
    }

    public function store(StoreClientRequest $request, ClientProfileDataNormalizer $clientProfileDataNormalizer): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $agency = $request->user()->agency;
        abort_if($agency === null, 403);

        $agency->clients()->create([
            ...$clientProfileDataNormalizer->normalize($request->validated()),
            'owner_id' => $request->user()->id,
        ]);

        return to_route('clients.index')->with('success', 'Client created successfully.');
    }

    public function update(
        UpdateClientRequest $request,
        Client $client,
        ClientProfileDataNormalizer $clientProfileDataNormalizer,
    ): RedirectResponse {
        $this->authorize('update', $client);

        $client->update($clientProfileDataNormalizer->normalize($request->validated()));

        return to_route('clients.show', $client)->with('success', 'Client updated successfully.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function maritalStatusOptions(): array
    {
        return collect(Client::MARITAL_STATUSES)
            ->map(fn (string $status): array => [
                'value' => $status,
                'label' => str($status)->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }
}
