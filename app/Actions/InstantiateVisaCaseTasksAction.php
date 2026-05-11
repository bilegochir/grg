<?php

namespace App\Actions;

use App\Models\VisaCase;
use App\Models\VisaTaskTemplate;
use App\Models\VisaType;

class InstantiateVisaCaseTasksAction
{
    public function execute(VisaCase $visaCase, VisaType $visaType): void
    {
        $visaType->loadMissing('taskTemplates');

        $visaType->taskTemplates
            ->sortBy('position')
            ->each(function (VisaTaskTemplate $template) use ($visaCase): void {
                $visaCase->tasks()->create([
                    'visa_task_template_id' => $template->id,
                    'visa_workflow_stage_id' => $template->visa_workflow_stage_id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'status' => 'pending',
                    'position' => $template->position,
                    'due_at' => $template->due_days !== null ? now()->addDays($template->due_days)->toDateString() : null,
                    'is_required' => $template->is_required,
                    'is_client_visible' => $template->is_client_visible,
                ]);
            });
    }
}
