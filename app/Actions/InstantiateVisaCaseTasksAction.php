<?php

namespace App\Actions;

use App\Models\VisaCase;
use App\Models\VisaTaskTemplate;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

class InstantiateVisaCaseTasksAction
{
    public function execute(
        VisaCase $visaCase,
        VisaType $visaType,
        ?VisaWorkflowStage $stage = null,
        bool $includeSharedTemplates = true,
    ): int
    {
        $visaType->loadMissing('taskTemplates');
        $existingTemplateIds = $visaCase->tasks()
            ->whereNotNull('visa_task_template_id')
            ->pluck('visa_task_template_id')
            ->all();
        $nextPosition = ((int) $visaCase->tasks()->max('position')) + 1;
        $createdCount = 0;

        $visaType->taskTemplates
            ->filter(function (VisaTaskTemplate $template) use ($stage, $includeSharedTemplates): bool {
                if ($template->visa_workflow_stage_id === null) {
                    return $includeSharedTemplates;
                }

                if ($stage === null) {
                    return true;
                }

                return $template->visa_workflow_stage_id === $stage->id;
            })
            ->sortBy(fn (VisaTaskTemplate $template) => sprintf(
                '%s-%05d',
                $template->visa_workflow_stage_id === null ? '0' : '1',
                $template->position,
            ))
            ->each(function (VisaTaskTemplate $template) use ($visaCase, &$existingTemplateIds, &$nextPosition, &$createdCount): void {
                if (in_array($template->id, $existingTemplateIds, true)) {
                    return;
                }

                $visaCase->tasks()->create([
                    'visa_task_template_id' => $template->id,
                    'visa_workflow_stage_id' => $template->visa_workflow_stage_id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'status' => 'pending',
                    'position' => $nextPosition,
                    'due_at' => $template->due_days !== null ? now()->addDays($template->due_days)->toDateString() : null,
                    'is_required' => $template->is_required,
                    'is_client_visible' => $template->is_client_visible,
                ]);

                $existingTemplateIds[] = $template->id;
                $nextPosition++;
                $createdCount++;
            });

        return $createdCount;
    }
}
