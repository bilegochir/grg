<?php

namespace App\Http\Controllers;

use App\Actions\RecordActivityAction;
use App\Models\VisaWorkflowStage;
use App\Models\VisaCase;
use App\Models\VisaCaseTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VisaCaseTaskController extends Controller
{
    public function store(
        Request $request,
        VisaCase $case,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertCaseAccess($request->user(), $case);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'stage_id' => ['nullable', 'integer', 'exists:visa_workflow_stages,id'],
        ]);

        if (! empty($data['assigned_to_user_id'])) {
            $this->workspace()->assertCanAssign($request->user());
            $this->workspace()->assertAssignableUser($request->user(), (int) $data['assigned_to_user_id'], $case->branch_id);
        }

        $stageId = $data['stage_id'] ?? $case->current_stage_id;

        if ($stageId !== null) {
            $stage = VisaWorkflowStage::query()->findOrFail($stageId);
            abort_unless($stage->visa_type_id === $case->visa_type_id, 422);
        }

        $task = $case->tasks()->create([
            'visa_workflow_stage_id' => $stageId,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'name' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'position' => ((int) $case->tasks()->max('position')) + 1,
            'due_at' => $data['due_at'] ?? null,
            'is_required' => false,
            'is_client_visible' => false,
        ]);

        $recordActivity->execute(
            $case,
            'visa_case.task_created',
            sprintf('Task created: %s.', $task->name),
            $request->user(),
            ['task_id' => $task->id],
        );

        return back()->with('success', 'Task created.');
    }

    public function update(
        Request $request,
        VisaCase $case,
        VisaCaseTask $task,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        abort_unless($task->visa_case_id === $case->id, 404);
        $this->workspace()->assertCaseAccess($request->user(), $case);
        $this->workspace()->assertTaskAccess($request->user(), $task);

        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,skipped'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('assigned_to_user_id', $data) && $data['assigned_to_user_id']) {
            $this->workspace()->assertCanAssign($request->user());
            $this->workspace()->assertAssignableUser($request->user(), (int) $data['assigned_to_user_id'], $case->branch_id);
        }

        $task->forceFill([
            'status' => $data['status'],
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
        ])->save();

        $recordActivity->execute(
            $case,
            'visa_case.task_updated',
            sprintf('Task updated: %s.', $task->name),
            $request->user(),
            ['task_id' => $task->id, 'status' => $task->status],
        );

        return back()->with('success', 'Task updated.');
    }
}
