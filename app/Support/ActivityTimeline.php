<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\Note;
use App\Models\Task;
use App\Models\VisaCase;
use Illuminate\Support\Collection;

class ActivityTimeline
{
    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function forClient(Client $client): Collection
    {
        $client->loadMissing([
            'crmNotes.author:id,name',
            'attachments.uploader:id,name',
            'visaCases.assignee:id,name',
            'tasks.assignee:id,name',
        ]);

        return collect([
            [
                'type' => 'client_created',
                'title' => 'Client created',
                'description' => $client->full_name.' was added to the CRM.',
                'created_at' => $client->created_at?->toIso8601String(),
                'meta' => [
                    'status' => $client->status->label(),
                ],
            ],
            ...$client->crmNotes->map(fn (Note $note): array => [
                'type' => 'note',
                'title' => 'Note added',
                'description' => $note->body,
                'created_at' => $note->created_at?->toIso8601String(),
                'meta' => [
                    'author' => $note->author?->name,
                ],
            ]),
            ...$client->attachments->map(fn (Attachment $attachment): array => [
                'type' => 'attachment',
                'title' => 'Attachment uploaded',
                'description' => $attachment->original_name,
                'created_at' => $attachment->created_at?->toIso8601String(),
                'meta' => [
                    'uploader' => $attachment->uploader?->name,
                    'size' => $attachment->human_size,
                ],
            ]),
            ...$client->visaCases->map(fn (VisaCase $visaCase): array => [
                'type' => 'visa_case',
                'title' => 'Visa case opened',
                'description' => "{$visaCase->reference_code} • {$visaCase->visa_type} • {$visaCase->status->label()}",
                'created_at' => $visaCase->created_at?->toIso8601String(),
                'meta' => [
                    'assignee' => $visaCase->assignee?->name,
                    'country' => $visaCase->destination_country,
                ],
            ]),
            ...$client->tasks->map(fn (Task $task): array => [
                'type' => 'task',
                'title' => 'Task tracked',
                'description' => "{$task->title} • {$task->status->label()}",
                'created_at' => $task->created_at?->toIso8601String(),
                'meta' => [
                    'assignee' => $task->assignee?->name,
                    'priority' => $task->priority->label(),
                ],
            ]),
        ])->filter(fn (array $item): bool => filled($item['created_at']))
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function forVisaCase(VisaCase $visaCase): Collection
    {
        $visaCase->loadMissing([
            'crmNotes.author:id,name',
            'attachments.uploader:id,name',
            'tasks.assignee:id,name',
            'client:id,full_name',
            'assignee:id,name',
        ]);

        return collect([
            [
                'type' => 'visa_case_created',
                'title' => 'Visa case created',
                'description' => "{$visaCase->reference_code} was opened for {$visaCase->client?->full_name}.",
                'created_at' => $visaCase->created_at?->toIso8601String(),
                'meta' => [
                    'status' => $visaCase->status->label(),
                    'assignee' => $visaCase->assignee?->name,
                ],
            ],
            ...$visaCase->crmNotes->map(fn (Note $note): array => [
                'type' => 'note',
                'title' => 'Note added',
                'description' => $note->body,
                'created_at' => $note->created_at?->toIso8601String(),
                'meta' => [
                    'author' => $note->author?->name,
                ],
            ]),
            ...$visaCase->attachments->map(fn (Attachment $attachment): array => [
                'type' => 'attachment',
                'title' => 'Attachment uploaded',
                'description' => $attachment->original_name,
                'created_at' => $attachment->created_at?->toIso8601String(),
                'meta' => [
                    'uploader' => $attachment->uploader?->name,
                    'size' => $attachment->human_size,
                ],
            ]),
            ...$visaCase->tasks->map(fn (Task $task): array => [
                'type' => 'task',
                'title' => 'Task tracked',
                'description' => "{$task->title} • {$task->status->label()}",
                'created_at' => $task->created_at?->toIso8601String(),
                'meta' => [
                    'assignee' => $task->assignee?->name,
                    'priority' => $task->priority->label(),
                ],
            ]),
        ])->filter(fn (array $item): bool => filled($item['created_at']))
            ->sortByDesc('created_at')
            ->values();
    }
}
