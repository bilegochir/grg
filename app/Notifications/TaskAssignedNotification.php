<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly User $actor,
    ) {
        $this->task->loadMissing([
            'client:id,full_name',
            'visaCase:id,reference_code',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isSelfAssigned = $notifiable instanceof User && $notifiable->is($this->actor);

        return [
            'kind' => 'task_assigned',
            'title' => $isSelfAssigned ? 'Task created' : 'New task assigned',
            'message' => $isSelfAssigned
                ? "You added \"{$this->task->title}\" to your task list."
                : "{$this->actor->name} assigned \"{$this->task->title}\" to you.",
            'context' => $this->contextLabel(),
            'action_url' => $this->actionUrl(),
            'action_label' => 'Open task',
            'resource_id' => $this->task->id,
            'resource_type' => 'task',
            'resource_label' => $this->task->title,
        ];
    }

    private function actionUrl(): string
    {
        if ($this->task->visaCase !== null) {
            return route('visa-cases.show', $this->task->visaCase);
        }

        if ($this->task->client !== null) {
            return route('clients.show', $this->task->client);
        }

        return route('tasks.index');
    }

    private function contextLabel(): ?string
    {
        $segments = array_filter([
            $this->task->client?->full_name,
            $this->task->visaCase?->reference_code,
        ]);

        if ($segments === []) {
            return null;
        }

        return implode(' / ', $segments);
    }
}
