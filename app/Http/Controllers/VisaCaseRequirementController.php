<?php

namespace App\Http\Controllers;

use App\Enums\VisaRequirementStatus;
use App\Http\Requests\UpdateVisaCaseRequirementRequest;
use App\Models\VisaCase;
use App\Models\VisaCaseRequirement;
use Illuminate\Http\RedirectResponse;

class VisaCaseRequirementController extends Controller
{
    public function update(
        UpdateVisaCaseRequirementRequest $request,
        VisaCase $visaCase,
        VisaCaseRequirement $requirement,
    ): RedirectResponse {
        $this->authorize('update', $visaCase);

        abort_unless($requirement->visa_case_id === $visaCase->id, 404);

        $validated = $request->validated();
        $status = array_key_exists('status', $validated)
            ? VisaRequirementStatus::from($validated['status'])
            : $requirement->status;

        if (array_key_exists('is_completed', $validated) && ! array_key_exists('status', $validated)) {
            $status = (bool) $validated['is_completed']
                ? VisaRequirementStatus::Verified
                : VisaRequirementStatus::Pending;
        }

        $isCompleted = in_array($status, [VisaRequirementStatus::Verified, VisaRequirementStatus::Waived], true);

        $requirement->update([
            'status' => $status,
            'due_at' => array_key_exists('due_at', $validated) ? $validated['due_at'] : $requirement->due_at,
            'review_notes' => array_key_exists('review_notes', $validated) ? $validated['review_notes'] : $requirement->review_notes,
            'requested_at' => $status === VisaRequirementStatus::Requested
                ? ($requirement->requested_at ?? now())
                : $requirement->requested_at,
            'received_at' => in_array($status, [VisaRequirementStatus::Received, VisaRequirementStatus::Verified, VisaRequirementStatus::Waived], true)
                ? ($requirement->received_at ?? now())
                : $requirement->received_at,
            'reviewed_at' => $isCompleted ? ($requirement->reviewed_at ?? now()) : null,
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? ($requirement->completed_at ?? now()) : null,
        ]);

        return to_route('visa-cases.show', $visaCase)->with('success', 'Requirement checklist updated.');
    }
}
