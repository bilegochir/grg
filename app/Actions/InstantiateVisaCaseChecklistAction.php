<?php

namespace App\Actions;

use App\Models\VisaCase;
use App\Models\VisaType;

class InstantiateVisaCaseChecklistAction
{
    public function execute(VisaCase $visaCase, ?VisaType $visaType = null): void
    {
        $visaType ??= $visaCase->visaType()->with('documentTemplates')->firstOrFail();

        foreach ($visaType->documentTemplates as $template) {
            $visaCase->documents()->create([
                'document_template_id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'client_instructions' => $template->client_instructions,
                'agent_guidance' => $template->agent_guidance,
                'sample_hint' => $template->sample_hint,
                'accepted_file_types' => $template->accepted_file_types,
                'max_files' => $template->max_files,
                'max_file_size_mb' => $template->max_file_size_mb,
                'due_days' => $template->due_days,
                'is_repeatable' => $template->is_repeatable,
                'position' => $template->position,
                'status' => 'pending',
                'is_required' => $template->is_required,
                'tracks_expiry' => $template->tracks_expiry,
            ]);
        }
    }
}
