<?php

namespace App\Actions;

use App\Enums\ApplicantNotificationEvent;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaWorkflowStage;
use Illuminate\Support\Facades\DB;
use LogicException;

class UpdateVisaCaseStageAction
{
    public function __construct(
        private readonly RecordActivityAction $recordActivity,
        private readonly DispatchApplicantCaseNotificationAction $dispatchNotification,
    ) {
    }

    public function execute(VisaCase $visaCase, VisaWorkflowStage $toStage, ?User $user = null): VisaCase
    {
        $fromStage = $visaCase->currentStage;

        if ($visaCase->visa_type_id !== $toStage->visa_type_id) {
            throw new LogicException('Cannot move a case to a stage from another visa workflow.');
        }

        return DB::transaction(function () use ($visaCase, $fromStage, $toStage, $user): VisaCase {
            $visaCase->forceFill([
                'current_stage_id' => $toStage->id,
                'closed_at' => $toStage->is_closed ? now() : null,
            ])->save();

            $visaCase->stageHistories()->create([
                'from_stage_id' => $fromStage?->id,
                'to_stage_id' => $toStage->id,
                'changed_by_user_id' => $user?->id,
                'changed_at' => now(),
            ]);

            $this->recordActivity->execute(
                $visaCase,
                'visa_case.stage_updated',
                sprintf(
                    'Case moved from %s to %s.',
                    $fromStage?->name ?? 'Created',
                    $toStage->name,
                ),
                $user,
            );

            $freshCase = $visaCase->fresh(['applicant', 'country', 'visaType', 'currentStage']);

            DB::afterCommit(function () use ($freshCase, $user): void {
                $this->dispatchNotification->execute(
                    $freshCase,
                    ApplicantNotificationEvent::CaseStatusChanges,
                    user: $user,
                    senderType: 'system',
                );
            });

            return $freshCase->fresh(['currentStage', 'stageHistories.fromStage', 'stageHistories.toStage']);
        });
    }
}
