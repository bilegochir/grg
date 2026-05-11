<?php

namespace App\Actions;

use App\Models\VisaType;
use App\Models\VisaWorkflowStage;

class ProvisionVisaTypeDefaultsAction
{
    public function execute(VisaType $visaType): void
    {
        $defaults = [
            ['name' => 'Documents Pending', 'slug' => 'documents-pending', 'position' => 1, 'color' => 'amber', 'is_default' => true, 'is_closed' => false],
            ['name' => 'Under Review', 'slug' => 'under-review', 'position' => 2, 'color' => 'blue', 'is_default' => false, 'is_closed' => false],
            ['name' => 'Submitted to Embassy', 'slug' => 'submitted-to-embassy', 'position' => 3, 'color' => 'violet', 'is_default' => false, 'is_closed' => false],
            ['name' => 'Decision', 'slug' => 'decision', 'position' => 4, 'color' => 'emerald', 'is_default' => false, 'is_closed' => false],
            ['name' => 'Closed', 'slug' => 'closed', 'position' => 5, 'color' => 'slate', 'is_default' => false, 'is_closed' => true],
        ];

        foreach ($defaults as $stage) {
            VisaWorkflowStage::query()->firstOrCreate(
                [
                    'visa_type_id' => $visaType->id,
                    'slug' => $stage['slug'],
                ],
                $stage,
            );
        }
    }
}
