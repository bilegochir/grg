<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

class UpdateLeadStatusAction
{
    public function __construct(
        private readonly RecordActivityAction $recordActivity,
    ) {
    }

    public function execute(Lead $lead, LeadStatus $status, ?User $user = null): Lead
    {
        $currentStatus = $lead->status;

        if ($currentStatus === $status) {
            return $lead;
        }

        $lead->forceFill([
            'status' => $status,
        ])->save();

        $lead->statusHistories()->create([
            'from_status' => $currentStatus?->value,
            'to_status' => $status->value,
            'changed_by_user_id' => $user?->id,
            'changed_at' => now(),
        ]);

        $this->recordActivity->execute(
            $lead,
            'lead.status_updated',
            sprintf('Lead status changed from %s to %s.', $currentStatus?->label() ?? 'Unknown', $status->label()),
            $user,
            [
                'from' => $currentStatus?->value,
                'to' => $status->value,
            ],
        );

        return $lead->fresh(['statusHistories.user']);
    }
}
