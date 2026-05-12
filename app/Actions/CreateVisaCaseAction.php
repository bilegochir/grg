<?php

namespace App\Actions;

use App\Models\Applicant;
use App\Models\Branch;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateVisaCaseAction
{
    public function __construct(
        private readonly RecordActivityAction $recordActivity,
        private readonly InstantiateVisaCaseChecklistAction $instantiateChecklist,
        private readonly InstantiateVisaCaseTasksAction $instantiateTasks,
    ) {
    }

    public function execute(Applicant $applicant, array $attributes, ?User $user = null): VisaCase
    {
        return DB::transaction(function () use ($applicant, $attributes, $user): VisaCase {
            $visaType = VisaType::query()
                ->with(['country', 'workflowStages'])
                ->findOrFail($attributes['visa_type_id']);

            /** @var VisaWorkflowStage $defaultStage */
            $defaultStage = $visaType->workflowStages
                ->firstWhere('is_default', true)
                ?? $visaType->workflowStages->sortBy('position')->first();

            $assignedUser = ! empty($attributes['assigned_to_user_id'])
                ? User::query()->with('branch')->find($attributes['assigned_to_user_id'])
                : null;

            $branchId = $attributes['branch_id']
                ?? $assignedUser?->branch_id
                ?? $user?->branch_id;

            if ($branchId !== null) {
                Branch::query()->findOrFail($branchId);
            }

            $visaCase = VisaCase::create([
                'applicant_id' => $applicant->id,
                'visa_type_id' => $visaType->id,
                'target_country_id' => $visaType->target_country_id,
                'branch_id' => $branchId,
                'assigned_to_user_id' => $attributes['assigned_to_user_id'] ?? null,
                'current_stage_id' => $defaultStage?->id,
                'priority' => $attributes['priority'],
                'reference_code' => $this->generateReferenceCode(),
                'expected_submission_at' => $attributes['expected_submission_at'] ?? null,
                'expected_decision_at' => $attributes['expected_decision_at'] ?? null,
            ]);

            if ($defaultStage !== null) {
                $visaCase->stageHistories()->create([
                    'from_stage_id' => null,
                    'to_stage_id' => $defaultStage->id,
                    'changed_by_user_id' => $user?->id,
                    'changed_at' => now(),
                ]);
            }

            if (! empty($attributes['internal_note'])) {
                $visaCase->notes()->create([
                    'body' => $attributes['internal_note'],
                    'created_by_user_id' => $user?->id,
                    'is_client_visible' => false,
                ]);
            }

            if (! empty($attributes['client_note'])) {
                $visaCase->notes()->create([
                    'body' => $attributes['client_note'],
                    'created_by_user_id' => $user?->id,
                    'is_client_visible' => true,
                ]);
            }

            $this->instantiateChecklist->execute($visaCase, $visaType);

            if ($defaultStage !== null) {
                $this->instantiateTasks->execute($visaCase, $visaType, $defaultStage, true);
            }

            $this->recordActivity->execute(
                $visaCase,
                'visa_case.created',
                sprintf('Visa case created for %s.', $applicant->full_name),
                $user,
                [
                    'visa_type' => $visaType->name,
                    'country' => $visaType->country->name,
                ],
            );

            return $visaCase->load(['applicant', 'country', 'branch', 'visaType', 'currentStage', 'assignedTo']);
        });
    }

    private function generateReferenceCode(): string
    {
        do {
            $reference = sprintf('VC-%s-%s', now()->format('ymd'), Str::upper(Str::random(4)));
        } while (VisaCase::query()->where('reference_code', $reference)->exists());

        return $reference;
    }
}
