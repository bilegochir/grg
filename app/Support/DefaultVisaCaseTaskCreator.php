<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseTaskTemplate;

class DefaultVisaCaseTaskCreator
{
    public function __construct(private DefaultVisaCaseTaskTemplateSeeder $defaultVisaCaseTaskTemplateSeeder)
    {
    }

    public function createForCurrentStatus(VisaCase $visaCase, User $creator): void
    {
        $agency = $visaCase->agency()->first();

        if ($agency === null) {
            return;
        }

        $this->defaultVisaCaseTaskTemplateSeeder->seed($agency);

        $templates = VisaCaseTaskTemplate::query()
            ->where('agency_id', $agency->id)
            ->where('visa_case_status', $visaCase->status->value)
            ->orderBy('sort_order')
            ->get();

        foreach ($templates as $template) {
            $visaCase->tasks()->firstOrCreate(
                [
                    'visa_case_task_template_id' => $template->id,
                ],
                [
                    'agency_id' => $visaCase->agency_id,
                    'client_id' => $visaCase->client_id,
                    'assigned_user_id' => $visaCase->assigned_user_id,
                    'created_by_id' => $creator->id,
                    'title' => $template->title,
                    'description' => $template->description,
                    'status' => TaskStatus::Todo,
                    'priority' => $template->priority,
                    'due_at' => $template->due_in_days === null ? null : now()->addDays($template->due_in_days),
                ],
            );
        }
    }
}
