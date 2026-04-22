<?php

namespace App\Support;

use App\Enums\TaskPriority;
use App\Enums\VisaCaseStatus;
use App\Models\Agency;

class DefaultVisaCaseTaskTemplateSeeder
{
    public function seed(Agency $agency): void
    {
        if ($agency->visaCaseTaskTemplates()->exists()) {
            return;
        }

        foreach ($this->defaultTemplates() as $status => $templates) {
            foreach ($templates as $index => $template) {
                $agency->visaCaseTaskTemplates()->create([
                    'visa_case_status' => $status,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'priority' => $template['priority'],
                    'due_in_days' => $template['due_in_days'],
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }

    /**
     * @return array<string, list<array{
     *     title: string,
     *     description: string,
     *     priority: TaskPriority,
     *     due_in_days: int
     * }>>
     */
    private function defaultTemplates(): array
    {
        return [
            VisaCaseStatus::Intake->value => [
                [
                    'title' => 'Send intake checklist to client',
                    'description' => 'Share the case checklist, confirm the target visa stream, and explain the first document handoff.',
                    'priority' => TaskPriority::High,
                    'due_in_days' => 1,
                ],
                [
                    'title' => 'Confirm case facts and passport details',
                    'description' => 'Double-check identity details, passport validity, and the core filing timeline before moving forward.',
                    'priority' => TaskPriority::Medium,
                    'due_in_days' => 2,
                ],
            ],
            VisaCaseStatus::DocumentsPending->value => [
                [
                    'title' => 'Follow up on missing required documents',
                    'description' => 'Review outstanding checklist items and chase anything the client still needs to upload.',
                    'priority' => TaskPriority::High,
                    'due_in_days' => 2,
                ],
                [
                    'title' => 'Review newly uploaded evidence',
                    'description' => 'Check incoming files for completeness and note any follow-up or replacement documents.',
                    'priority' => TaskPriority::Medium,
                    'due_in_days' => 4,
                ],
            ],
            VisaCaseStatus::ReadyToFile->value => [
                [
                    'title' => 'Run final application review',
                    'description' => 'Verify the checklist is complete, confirm the facts are consistent, and clear the case for filing.',
                    'priority' => TaskPriority::High,
                    'due_in_days' => 1,
                ],
                [
                    'title' => 'Prepare filing package and payment',
                    'description' => 'Assemble the final submission pack, confirm payment steps, and stage the application for submission.',
                    'priority' => TaskPriority::Medium,
                    'due_in_days' => 2,
                ],
            ],
            VisaCaseStatus::Submitted->value => [
                [
                    'title' => 'Confirm submission receipt with client',
                    'description' => 'Send confirmation, expected next steps, and any reference numbers now that the application is lodged.',
                    'priority' => TaskPriority::Medium,
                    'due_in_days' => 1,
                ],
                [
                    'title' => 'Track biometrics or interview requests',
                    'description' => 'Monitor for post-submission requests and make sure the client is ready for the next action.',
                    'priority' => TaskPriority::Medium,
                    'due_in_days' => 7,
                ],
            ],
            VisaCaseStatus::Approved->value => [
                [
                    'title' => 'Send approval summary and next steps',
                    'description' => 'Share the outcome, grant conditions, and immediate actions the client needs to take next.',
                    'priority' => TaskPriority::Medium,
                    'due_in_days' => 1,
                ],
                [
                    'title' => 'Capture post-approval travel timeline',
                    'description' => 'Log arrival, enrolment, or relocation timing so the case handoff stays organized.',
                    'priority' => TaskPriority::Low,
                    'due_in_days' => 5,
                ],
            ],
            VisaCaseStatus::Rejected->value => [
                [
                    'title' => 'Review refusal reasons',
                    'description' => 'Read the decision outcome carefully and summarize the main refusal points for the team and client.',
                    'priority' => TaskPriority::High,
                    'due_in_days' => 1,
                ],
                [
                    'title' => 'Prepare rework or appeal recommendation',
                    'description' => 'Outline the best next-step recommendation, including reapplication, appeal, or close-out options.',
                    'priority' => TaskPriority::High,
                    'due_in_days' => 3,
                ],
            ],
            VisaCaseStatus::Closed->value => [
                [
                    'title' => 'Archive case documents',
                    'description' => 'Make sure the final documents and notes are filed cleanly before the case is fully closed.',
                    'priority' => TaskPriority::Low,
                    'due_in_days' => 1,
                ],
                [
                    'title' => 'Request client feedback',
                    'description' => 'Close the loop with a short feedback request and capture any referral opportunities.',
                    'priority' => TaskPriority::Low,
                    'due_in_days' => 5,
                ],
            ],
        ];
    }
}
