<?php

namespace App\Support;

use App\Enums\VisaRequirementStatus;
use App\Models\VisaCase;
use App\Models\VisaRequirementTemplate;

class VisaRequirementChecklistSynchronizer
{
    public function sync(VisaCase $visaCase): ?VisaRequirementTemplate
    {
        $template = VisaRequirementTemplate::query()
            ->matching($visaCase->destination_country, $visaCase->visa_type)
            ->with('items')
            ->first();

        if ($template === null) {
            $visaCase->requirements()->delete();

            return null;
        }

        $existingRequirements = $visaCase->requirements()
            ->get()
            ->keyBy('visa_requirement_item_id');

        $templateItemIds = [];

        foreach ($template->items as $item) {
            $existingRequirement = $existingRequirements->get($item->id);

            $visaCase->requirements()->updateOrCreate(
                ['visa_requirement_item_id' => $item->id],
                [
                    'category' => $item->category,
                    'label' => $item->label,
                    'help_text' => $item->help_text,
                    'is_required' => $item->is_required,
                    'sort_order' => $item->sort_order,
                    'status' => $existingRequirement?->status ?? VisaRequirementStatus::Pending,
                    'due_at' => $existingRequirement?->due_at,
                    'requested_at' => $existingRequirement?->requested_at,
                    'received_at' => $existingRequirement?->received_at,
                    'reviewed_at' => $existingRequirement?->reviewed_at,
                    'review_notes' => $existingRequirement?->review_notes,
                    'is_completed' => $existingRequirement?->is_completed ?? false,
                    'completed_at' => $existingRequirement?->completed_at,
                ],
            );

            $templateItemIds[] = $item->id;
        }

        $visaCase->requirements()
            ->whereNotIn('visa_requirement_item_id', $templateItemIds)
            ->delete();

        return $template;
    }
}
