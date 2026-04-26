<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\CrmActivity;
use App\Models\Note;
use App\Models\Task;
use App\Models\VisaCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ActivityTimeline
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function forClient(Client $client): Collection
    {
        $client->loadMissing([
            'crmNotes.author:id,name',
            'crmActivities.actor:id,name',
            'attachments.uploader:id,name',
            'visaCases.assignee:id,name',
            'tasks.assignee:id,name',
        ]);

        return self::sortTimeline(collect([
            [
                'type' => 'client_created',
                'title' => 'Client created',
                'description' => $client->full_name.' was added to the CRM.',
                'created_at' => $client->created_at?->toIso8601String(),
                'sort_id' => $client->id,
                'meta' => [
                    'status' => $client->status->label(),
                ],
            ],
            ...$client->crmNotes->map(fn (Note $note): array => [
                'type' => 'note',
                'title' => 'Note added',
                'description' => $note->body,
                'created_at' => $note->created_at?->toIso8601String(),
                'sort_id' => $note->id,
                'meta' => [
                    'author' => $note->author?->name,
                ],
            ]),
            ...$client->crmActivities->map(fn (CrmActivity $activity): array => [
                'type' => $activity->event_type,
                'title' => $activity->title,
                'description' => $activity->description ?? '',
                'created_at' => $activity->created_at?->toIso8601String(),
                'sort_id' => $activity->id,
                'meta' => [
                    'author' => $activity->actor?->name,
                ],
            ]),
            ...$client->attachments->map(fn (Attachment $attachment): array => [
                'type' => 'attachment',
                'title' => 'Attachment uploaded',
                'description' => $attachment->original_name,
                'created_at' => $attachment->created_at?->toIso8601String(),
                'sort_id' => $attachment->id,
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
                'sort_id' => $visaCase->id,
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
                'sort_id' => $task->id,
                'meta' => [
                    'assignee' => $task->assignee?->name,
                    'priority' => $task->priority->label(),
                ],
            ]),
        ]));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function forVisaCase(VisaCase $visaCase): Collection
    {
        $visaCase->loadMissing([
            'crmNotes.author:id,name',
            'crmActivities.actor:id,name',
            'attachments.uploader:id,name',
            'tasks.assignee:id,name',
            'client:id,full_name',
            'assignee:id,name',
        ]);

        return self::sortTimeline(collect([
            [
                'type' => 'visa_case_created',
                'title' => 'Visa case created',
                'description' => "{$visaCase->reference_code} was opened for {$visaCase->client?->full_name}.",
                'created_at' => $visaCase->created_at?->toIso8601String(),
                'sort_id' => $visaCase->id,
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
                'sort_id' => $note->id,
                'meta' => [
                    'author' => $note->author?->name,
                ],
            ]),
            ...$visaCase->crmActivities->map(fn (CrmActivity $activity): array => [
                'type' => $activity->event_type,
                'title' => $activity->title,
                'description' => $activity->description ?? '',
                'created_at' => $activity->created_at?->toIso8601String(),
                'sort_id' => $activity->id,
                'meta' => [
                    'author' => $activity->actor?->name,
                ],
            ]),
            ...$visaCase->attachments->map(fn (Attachment $attachment): array => [
                'type' => 'attachment',
                'title' => 'Attachment uploaded',
                'description' => $attachment->original_name,
                'created_at' => $attachment->created_at?->toIso8601String(),
                'sort_id' => $attachment->id,
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
                'sort_id' => $task->id,
                'meta' => [
                    'assignee' => $task->assignee?->name,
                    'priority' => $task->priority->label(),
                ],
            ]),
        ]));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private static function sortTimeline(Collection $items): Collection
    {
        return $items
            ->filter(fn (array $item): bool => filled($item['created_at']))
            ->sort(function (array $left, array $right): int {
                $createdAtComparison = Carbon::parse($right['created_at'])->getTimestamp()
                    <=> Carbon::parse($left['created_at'])->getTimestamp();

                if ($createdAtComparison !== 0) {
                    return $createdAtComparison;
                }

                $priorityComparison = self::timelinePriority($right['type'])
                    <=> self::timelinePriority($left['type']);

                if ($priorityComparison !== 0) {
                    return $priorityComparison;
                }

                return ((int) ($right['sort_id'] ?? 0)) <=> ((int) ($left['sort_id'] ?? 0));
            })
            ->map(fn (array $item): array => Arr::except($item, ['sort_id']))
            ->values();
    }

    private static function timelinePriority(string $type): int
    {
        return match ($type) {
            'client_updated', 'visa_case_updated' => 50,
            'note' => 40,
            'attachment' => 30,
            'task' => 20,
            'visa_case' => 15,
            'visa_case_created', 'client_created' => 10,
            default => 0,
        };
    }
}
