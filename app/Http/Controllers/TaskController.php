<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Support\TaskStatusTemplateResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, TaskStatusTemplateResolver $taskStatusTemplateResolver): Response
    {
        $this->authorize('viewAny', Task::class);

        $agency = $request->user()->agency;
        abort_if($agency === null, 403);
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');
        $priority = (string) $request->input('priority', 'all');

        $openTaskStatuses = array_map(
            static fn (TaskStatus $status): string => $status->value,
            TaskStatus::open(),
        );
        $taskStatusLabels = $taskStatusTemplateResolver->labelsByStatus($agency);

        return Inertia::render('tasks/Index', [
            'tasks' => Task::query()
                ->forAgency($agency)
                ->with(['client:id,full_name', 'visaCase:id,reference_code', 'assignee:id,name'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($searchQuery) use ($search): void {
                        $searchQuery
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('full_name', 'like', "%{$search}%"))
                            ->orWhereHas('visaCase', fn ($visaCaseQuery) => $visaCaseQuery->where('reference_code', 'like', "%{$search}%"))
                            ->orWhereHas('assignee', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($priority !== 'all', fn ($query) => $query->where('priority', $priority))
                ->latest()
                ->get()
                ->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status->value,
                    'status_label' => $taskStatusLabels[$task->status->value] ?? $task->status->label(),
                    'priority' => $task->priority->value,
                    'priority_label' => $task->priority->label(),
                    'due_at' => $task->due_at?->toIso8601String(),
                    'completed_at' => $task->completed_at?->toIso8601String(),
                    'client_name' => $task->client?->full_name,
                    'visa_case_reference' => $task->visaCase?->reference_code,
                    'assignee_name' => $task->assignee?->name,
                ])
                ->values(),
            'clients' => $agency->clients()
                ->orderBy('full_name')
                ->get(['id', 'full_name'])
                ->map(fn ($client): array => ['id' => $client->id, 'full_name' => $client->full_name])
                ->values(),
            'visaCases' => $agency->visaCases()
                ->orderByDesc('id')
                ->get(['id', 'reference_code'])
                ->map(fn ($visaCase): array => ['id' => $visaCase->id, 'reference_code' => $visaCase->reference_code])
                ->values(),
            'users' => $agency->users()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($user): array => ['id' => $user->id, 'name' => $user->name])
                ->values(),
            'statusOptions' => $taskStatusTemplateResolver->options($agency),
            'priorityOptions' => TaskPriority::options(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'priority' => $priority,
            ],
            'stats' => [
                'open' => Task::query()->forAgency($agency)->whereIn('status', $openTaskStatuses)->count(),
                'dueToday' => Task::query()
                    ->forAgency($agency)
                    ->whereIn('status', $openTaskStatuses)
                    ->whereDate('due_at', today())
                    ->count(),
                'overdue' => Task::query()
                    ->forAgency($agency)
                    ->whereIn('status', $openTaskStatuses)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->count(),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $agency = $request->user()->agency;
        abort_if($agency === null, 403);

        $agency->tasks()->create([
            ...$request->validated(),
            'created_by_id' => $request->user()->id,
        ]);

        return to_route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $status = TaskStatus::from($request->validated('status'));

        $task->update([
            'status' => $status,
            'completed_at' => $status === TaskStatus::Done ? now() : null,
        ]);

        return to_route('tasks.index')->with('success', 'Task updated successfully.');
    }
}
