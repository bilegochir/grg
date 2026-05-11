<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\Applicant;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;

class ConvertLeadToApplicantAction
{
    public function __construct(
        private readonly RecordActivityAction $recordActivity,
        private readonly UpdateLeadStatusAction $updateLeadStatus,
    ) {
    }

    public function execute(Lead $lead, array $applicantData, ?User $user = null): Applicant
    {
        if ($lead->converted_to_applicant_id !== null) {
            throw new LogicException('This lead has already been converted.');
        }

        return DB::transaction(function () use ($lead, $applicantData, $user): Applicant {
            $applicant = Applicant::create([
                'lead_id' => $lead->id,
                'first_name' => Arr::get($applicantData, 'first_name', $lead->first_name),
                'last_name' => Arr::get($applicantData, 'last_name', $lead->last_name),
                'email' => Arr::get($applicantData, 'email', $lead->email),
                'phone' => Arr::get($applicantData, 'phone', $lead->phone),
                'date_of_birth' => Arr::get($applicantData, 'date_of_birth'),
                'nationality' => Arr::get($applicantData, 'nationality', $lead->country_of_citizenship),
                'country_of_residence' => Arr::get($applicantData, 'country_of_residence'),
                'passport_number' => Arr::get($applicantData, 'passport_number'),
                'passport_country' => Arr::get($applicantData, 'passport_country'),
                'passport_issued_at' => Arr::get($applicantData, 'passport_issued_at'),
                'passport_expires_at' => Arr::get($applicantData, 'passport_expires_at'),
                'travel_history' => Arr::get($applicantData, 'travel_history', []),
                'metadata' => Arr::get($applicantData, 'metadata', []),
            ]);

            $applicant->tags()->sync($lead->tags()->pluck('tags.id'));

            $lead->forceFill([
                'converted_at' => now(),
                'converted_to_applicant_id' => $applicant->id,
            ])->save();

            $this->updateLeadStatus->execute($lead, LeadStatus::Applied, $user);

            $this->recordActivity->execute(
                $lead,
                'lead.converted',
                'Lead converted to applicant.',
                $user,
                ['applicant_id' => $applicant->id],
            );

            $this->recordActivity->execute(
                $applicant,
                'applicant.created_from_lead',
                'Applicant created from lead conversion.',
                $user,
                ['lead_id' => $lead->id],
            );

            return $applicant->load(['lead', 'tags']);
        });
    }
}
