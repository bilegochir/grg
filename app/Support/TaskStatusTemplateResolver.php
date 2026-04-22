<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Agency;
use App\Models\TaskStatusTemplate;

class TaskStatusTemplateResolver
{
    public function __construct(private DefaultTaskStatusTemplateSeeder $defaultTaskStatusTemplateSeeder)
    {
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(Agency $agency): array
    {
        return $this->templates($agency)
            ->map(fn (TaskStatusTemplate $template): array => [
                'value' => $template->status_key,
                'label' => $template->label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function labelsByStatus(Agency $agency): array
    {
        return $this->templates($agency)
            ->mapWithKeys(fn (TaskStatusTemplate $template): array => [$template->status_key => $template->label])
            ->all();
    }

    public function labelFor(Agency $agency, string|TaskStatus $status): string
    {
        $statusValue = $status instanceof TaskStatus ? $status->value : $status;
        $labels = $this->labelsByStatus($agency);

        return $labels[$statusValue] ?? TaskStatus::from($statusValue)->label();
    }

    /**
     * @return \Illuminate\Support\Collection<int, TaskStatusTemplate>
     */
    public function templates(Agency $agency)
    {
        $this->defaultTaskStatusTemplateSeeder->seed($agency);

        return TaskStatusTemplate::query()
            ->where('agency_id', $agency->id)
            ->orderBy('sort_order')
            ->get();
    }
}
