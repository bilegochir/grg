<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\VisaCaseStatus;
use App\Models\Client;
use App\Models\Task;
use App\Models\VisaCase;
use App\Support\TaskStatusTemplateResolver;
use App\Support\VisaCaseStatusTemplateResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        VisaCaseStatusTemplateResolver $visaCaseStatusTemplateResolver,
        TaskStatusTemplateResolver $taskStatusTemplateResolver,
    ): Response
    {
        $user = $request->user();
        $agency = $user->agency;

        abort_if($agency === null, 403);

        $openTaskStatuses = array_map(
            static fn (TaskStatus $status): string => $status->value,
            TaskStatus::open(),
        );

        $activeCaseStatuses = array_map(
            static fn (VisaCaseStatus $status): string => $status->value,
            VisaCaseStatus::active(),
        );

        $clientStatusCounts = Client::query()
            ->forAgency($agency)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $caseStatusCounts = VisaCase::query()
            ->forAgency($agency)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $taskStatusCounts = Task::query()
            ->forAgency($agency)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $taskPriorityCounts = Task::query()
            ->forAgency($agency)
            ->selectRaw('priority, count(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');
        $statusLabels = $visaCaseStatusTemplateResolver->labelsByStatus($agency);
        $taskStatusLabels = $taskStatusTemplateResolver->labelsByStatus($agency);

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalClients' => Client::query()->forAgency($agency)->count(),
                'activeCases' => VisaCase::query()->forAgency($agency)->whereIn('status', $activeCaseStatuses)->count(),
                'openTasks' => Task::query()->forAgency($agency)->whereIn('status', $openTaskStatuses)->count(),
                'overdueTasks' => Task::query()
                    ->forAgency($agency)
                    ->whereIn('status', $openTaskStatuses)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->count(),
            ],
            'charts' => [
                'clientStages' => collect(ClientStatus::cases())
                    ->map(fn (ClientStatus $status): array => [
                        'label' => $status->label(),
                        'value' => (int) $clientStatusCounts->get($status->value, 0),
                        'color' => match ($status) {
                            ClientStatus::Lead => '#94a3b8',
                            ClientStatus::Qualified => '#0ea5e9',
                            ClientStatus::Active => '#10b981',
                            ClientStatus::Closed => '#64748b',
                        },
                    ])
                    ->values(),
                'caseStages' => collect(VisaCaseStatus::cases())
                    ->map(fn (VisaCaseStatus $status): array => [
                        'label' => $statusLabels[$status->value] ?? $status->label(),
                        'value' => (int) $caseStatusCounts->get($status->value, 0),
                        'color' => match ($status) {
                            VisaCaseStatus::Intake => '#94a3b8',
                            VisaCaseStatus::DocumentsPending => '#f59e0b',
                            VisaCaseStatus::ReadyToFile => '#8b5cf6',
                            VisaCaseStatus::Submitted => '#0ea5e9',
                            VisaCaseStatus::Approved => '#10b981',
                            VisaCaseStatus::Rejected => '#f43f5e',
                            VisaCaseStatus::Closed => '#64748b',
                        },
                    ])
                    ->values(),
                'taskStatuses' => collect(TaskStatus::cases())
                    ->map(fn (TaskStatus $status): array => [
                        'label' => $taskStatusLabels[$status->value] ?? $status->label(),
                        'value' => (int) $taskStatusCounts->get($status->value, 0),
                        'color' => match ($status) {
                            TaskStatus::Todo => '#94a3b8',
                            TaskStatus::InProgress => '#0ea5e9',
                            TaskStatus::Done => '#10b981',
                        },
                    ])
                    ->values(),
                'taskPriorities' => collect(TaskPriority::cases())
                    ->map(fn (TaskPriority $priority): array => [
                        'label' => $priority->label(),
                        'value' => (int) $taskPriorityCounts->get($priority->value, 0),
                        'color' => match ($priority) {
                            TaskPriority::Low => '#94a3b8',
                            TaskPriority::Medium => '#0ea5e9',
                            TaskPriority::High => '#f59e0b',
                            TaskPriority::Urgent => '#f43f5e',
                        },
                    ])
                    ->values(),
            ],
            'recentClients' => Client::query()
                ->forAgency($agency)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'full_name' => $client->full_name,
                    'status' => $client->status->value,
                    'status_label' => $client->status->label(),
                    'destination_country' => $client->destination_country,
                    'created_at' => $client->created_at?->toDateTimeString(),
                ])
                ->values(),
            'upcomingTasks' => Task::query()
                ->forAgency($agency)
                ->with(['client:id,full_name', 'assignee:id,name'])
                ->whereIn('status', $openTaskStatuses)
                ->orderByRaw('due_at is null')
                ->orderBy('due_at')
                ->limit(5)
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status->value,
                    'status_label' => $taskStatusLabels[$task->status->value] ?? $task->status->label(),
                    'priority' => $task->priority->value,
                    'priority_label' => $task->priority->label(),
                    'due_at' => $task->due_at?->toIso8601String(),
                    'client_name' => $task->client?->full_name,
                    'assignee_name' => $task->assignee?->name,
                ])
                ->values(),
            'pipeline' => VisaCase::query()
                ->forAgency($agency)
                ->with(['client:id,full_name', 'assignee:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (VisaCase $visaCase): array => [
                    'id' => $visaCase->id,
                    'reference_code' => $visaCase->reference_code,
                    'visa_type' => $visaCase->visa_type,
                    'status' => $visaCase->status->value,
                    'status_label' => $statusLabels[$visaCase->status->value] ?? $visaCase->status->label(),
                    'destination_country' => $visaCase->destination_country,
                    'client_name' => $visaCase->client?->full_name,
                    'assignee_name' => $visaCase->assignee?->name,
                ])
                ->values(),
        ]);
    }
}
