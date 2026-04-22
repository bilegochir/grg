<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Agency;

class DefaultTaskStatusTemplateSeeder
{
    public function seed(Agency $agency): void
    {
        foreach (TaskStatus::cases() as $index => $status) {
            $agency->taskStatusTemplates()->firstOrCreate(
                ['status_key' => $status->value],
                [
                    'label' => $status->label(),
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
