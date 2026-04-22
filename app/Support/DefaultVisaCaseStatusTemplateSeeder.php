<?php

namespace App\Support;

use App\Enums\VisaCaseStatus;
use App\Models\Agency;

class DefaultVisaCaseStatusTemplateSeeder
{
    public function seed(Agency $agency): void
    {
        foreach (VisaCaseStatus::cases() as $index => $status) {
            $agency->visaCaseStatusTemplates()->firstOrCreate(
                ['status_key' => $status->value],
                [
                    'label' => $status->label(),
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
