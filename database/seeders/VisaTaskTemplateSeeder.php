<?php

namespace Database\Seeders;

use App\Models\VisaTaskTemplate;
use App\Models\VisaType;
use Illuminate\Database\Seeder;

class VisaTaskTemplateSeeder extends Seeder
{
    public function run(): void
    {
        VisaType::query()
            ->with('workflowStages:id,visa_type_id,slug')
            ->get()
            ->each(function (VisaType $visaType): void {
                $stages = $visaType->workflowStages->keyBy('slug');

                foreach ($this->sharedTemplates() as $template) {
                    $this->upsertTemplate($visaType, $template);
                }

                foreach ($this->documentsPendingTemplates($visaType) as $template) {
                    $this->upsertTemplate($visaType, [
                        ...$template,
                        'visa_workflow_stage_id' => $stages->get('documents-pending')?->id,
                    ]);
                }

                foreach ($this->underReviewTemplates($visaType) as $template) {
                    $this->upsertTemplate($visaType, [
                        ...$template,
                        'visa_workflow_stage_id' => $stages->get('under-review')?->id,
                    ]);
                }

                foreach ($this->submittedTemplates($visaType) as $template) {
                    $this->upsertTemplate($visaType, [
                        ...$template,
                        'visa_workflow_stage_id' => $stages->get('submitted-to-embassy')?->id,
                    ]);
                }

                foreach ($this->decisionTemplates() as $template) {
                    $this->upsertTemplate($visaType, [
                        ...$template,
                        'visa_workflow_stage_id' => $stages->get('decision')?->id,
                    ]);
                }

                foreach ($this->closedTemplates() as $template) {
                    $this->upsertTemplate($visaType, [
                        ...$template,
                        'visa_workflow_stage_id' => $stages->get('closed')?->id,
                    ]);
                }
            });
    }

    private function upsertTemplate(VisaType $visaType, array $template): void
    {
        VisaTaskTemplate::query()->updateOrCreate(
            [
                'visa_type_id' => $visaType->id,
                'slug' => $template['slug'],
            ],
            [
                'visa_workflow_stage_id' => $template['visa_workflow_stage_id'] ?? null,
                'name' => $template['name'],
                'description' => $template['description'],
                'position' => $template['position'],
                'due_days' => $template['due_days'],
                'is_required' => $template['is_required'],
                'is_client_visible' => $template['is_client_visible'],
            ],
        );
    }

    private function sharedTemplates(): array
    {
        return [
            [
                'slug' => 'review-intake-and-confirm-pathway',
                'name' => 'Review intake and confirm pathway',
                'description' => 'Check the lead or applicant background, confirm the chosen visa route still fits, and note any immediate eligibility risks before work begins.',
                'position' => 1,
                'due_days' => 1,
                'is_required' => true,
                'is_client_visible' => false,
            ],
            [
                'slug' => 'confirm-passport-and-profile-details',
                'name' => 'Confirm passport and profile details',
                'description' => 'Verify passport validity, legal name, date of birth, nationality, and contact details before deeper processing.',
                'position' => 2,
                'due_days' => 1,
                'is_required' => true,
                'is_client_visible' => false,
            ],
        ];
    }

    private function documentsPendingTemplates(VisaType $visaType): array
    {
        $templates = [
            [
                'slug' => 'send-checklist-and-timeline',
                'name' => 'Send checklist and timeline',
                'description' => 'Send the client-facing checklist, explain what is needed first, and set expectations for the next review point.',
                'position' => 1,
                'due_days' => 1,
                'is_required' => true,
                'is_client_visible' => false,
            ],
            [
                'slug' => 'review-core-documents-for-completeness',
                'name' => 'Review core documents for completeness',
                'description' => 'Check passport, identity, and baseline supporting documents for quality, translation needs, and missing pages.',
                'position' => 2,
                'due_days' => 3,
                'is_required' => true,
                'is_client_visible' => false,
            ],
        ];

        if ($visaType->financial_proof_required) {
            $templates[] = [
                'slug' => 'collect-financial-evidence',
                'name' => 'Collect financial evidence',
                'description' => 'Request and verify bank statements, sponsor funds, or other financial support evidence required for this visa type.',
                'position' => 3,
                'due_days' => 5,
                'is_required' => true,
                'is_client_visible' => true,
            ];
        }

        if ($visaType->police_clearance_required) {
            $templates[] = [
                'slug' => 'collect-police-clearance-documents',
                'name' => 'Collect police clearance documents',
                'description' => 'Request police clearance certificates and make sure issue dates and issuing countries match the case requirements.',
                'position' => 4,
                'due_days' => 7,
                'is_required' => true,
                'is_client_visible' => true,
            ];
        }

        if ($visaType->medical_required) {
            $templates[] = [
                'slug' => 'collect-medical-exam-evidence',
                'name' => 'Collect medical exam evidence',
                'description' => 'Request the medical booking or completed exam evidence and check whether panel physician instructions need to be sent.',
                'position' => 5,
                'due_days' => 7,
                'is_required' => true,
                'is_client_visible' => true,
            ];
        }

        return $templates;
    }

    private function underReviewTemplates(VisaType $visaType): array
    {
        $templates = [
            [
                'slug' => 'draft-forms-and-supporting-statements',
                'name' => 'Draft forms and supporting statements',
                'description' => 'Prepare visa forms, agent notes, and any supporting explanation letters needed for a clean submission pack.',
                'position' => 1,
                'due_days' => 2,
                'is_required' => true,
                'is_client_visible' => false,
            ],
            [
                'slug' => 'run-final-quality-check',
                'name' => 'Run final quality check',
                'description' => 'Review the full file for gaps, inconsistencies, expired evidence, naming issues, and signature readiness before lodgement.',
                'position' => 2,
                'due_days' => 4,
                'is_required' => true,
                'is_client_visible' => false,
            ],
        ];

        if ($visaType->biometrics_required || $visaType->interview_required) {
            $templates[] = [
                'slug' => 'confirm-post-submission-instructions',
                'name' => 'Confirm post-submission instructions',
                'description' => 'Prepare the client for biometrics, interview, or other embassy follow-up steps that may be triggered after submission.',
                'position' => 3,
                'due_days' => 5,
                'is_required' => true,
                'is_client_visible' => false,
            ];
        }

        return $templates;
    }

    private function submittedTemplates(VisaType $visaType): array
    {
        $templates = [
            [
                'slug' => 'record-submission-reference-and-dates',
                'name' => 'Record submission reference and dates',
                'description' => 'Log the filing date, application reference, portal confirmation, and any follow-up deadlines immediately after lodgement.',
                'position' => 1,
                'due_days' => 1,
                'is_required' => true,
                'is_client_visible' => false,
            ],
        ];

        if ($visaType->biometrics_required) {
            $templates[] = [
                'slug' => 'track-biometrics-completion',
                'name' => 'Track biometrics completion',
                'description' => 'Monitor the biometrics appointment and collect proof once the client has completed the required enrolment step.',
                'position' => 2,
                'due_days' => 10,
                'is_required' => true,
                'is_client_visible' => true,
            ];
        }

        if ($visaType->interview_required) {
            $templates[] = [
                'slug' => 'track-interview-booking-and-outcome',
                'name' => 'Track interview booking and outcome',
                'description' => 'Follow up on interview scheduling, prep needs, attendance, and any documents requested after the interview.',
                'position' => 3,
                'due_days' => 10,
                'is_required' => true,
                'is_client_visible' => true,
            ];
        }

        return $templates;
    }

    private function decisionTemplates(): array
    {
        return [
            [
                'slug' => 'review-decision-and-next-steps',
                'name' => 'Review decision and next steps',
                'description' => 'Read the outcome notice carefully, capture any conditions or refusal reasons, and note the exact next step required from the team.',
                'position' => 1,
                'due_days' => 1,
                'is_required' => true,
                'is_client_visible' => false,
            ],
            [
                'slug' => 'send-decision-update-to-applicant',
                'name' => 'Send decision update to applicant',
                'description' => 'Share the decision clearly with the applicant and explain the practical next action, whether that is travel, stamping, appeal review, or closeout.',
                'position' => 2,
                'due_days' => 1,
                'is_required' => true,
                'is_client_visible' => false,
            ],
        ];
    }

    private function closedTemplates(): array
    {
        return [
            [
                'slug' => 'archive-final-documents-and-notes',
                'name' => 'Archive final documents and notes',
                'description' => 'Make sure the final decision notice, issued visa evidence, and closing notes are stored cleanly for future reference.',
                'position' => 1,
                'due_days' => 2,
                'is_required' => true,
                'is_client_visible' => false,
            ],
        ];
    }
}
