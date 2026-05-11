<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VisaCaseTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,skipped'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $baseQuery = $this->workspace()->scopeTasks(VisaCaseTask::query(), $request->user())
            ->with([
                'visaCase.applicant:id,first_name,last_name',
                'visaCase.currentStage:id,name',
                'assignedTo:id,name',
                'stage:id,name',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('visaCase', function (Builder $caseQuery) use ($search): void {
                            $caseQuery
                                ->where('reference_code', 'like', "%{$search}%")
                                ->orWhereHas('applicant', function (Builder $applicantQuery) use ($search): void {
                                    $applicantQuery
                                        ->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['assigned_to'] ?? null, fn (Builder $query, int $assignedTo) => $query->where('assigned_to_user_id', $assignedTo));

        $tasks = (clone $baseQuery)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->orderBy('position')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (VisaCaseTask $task): array => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'status' => $task->status,
                'is_required' => $task->is_required,
                'due_at' => $task->due_at?->toDateString(),
                'completed_at' => $task->completed_at?->toDateTimeString(),
                'overdue' => $task->due_at?->isPast() && $task->status !== 'completed',
                'stage' => $task->stage?->name,
                'assignee' => $task->assignedTo?->name,
                'assigned_to_user_id' => $task->assigned_to_user_id,
                'case' => [
                    'id' => $task->visaCase->id,
                    'reference_code' => $task->visaCase->reference_code,
                    'applicant_name' => $task->visaCase->applicant->full_name,
                    'current_stage' => $task->visaCase->currentStage?->name,
                ],
            ]);

        $summaryQuery = clone $baseQuery;

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
                'assigned_to' => $filters['assigned_to'] ?? '',
            ],
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'open' => (clone $summaryQuery)->whereIn('status', ['pending', 'in_progress'])->count(),
                'overdue' => (clone $summaryQuery)->whereDate('due_at', '<', now()->toDateString())->where('status', '!=', 'completed')->count(),
                'completed' => (clone $summaryQuery)->where('status', 'completed')->count(),
            ],
            'agents' => $this->workspace()->scopeUsers(User::query(), $request->user())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'in_progress', 'label' => 'In progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'skipped', 'label' => 'Skipped'],
            ],
        ]);
    }
}
