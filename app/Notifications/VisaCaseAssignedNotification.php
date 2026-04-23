<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\VisaCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VisaCaseAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly VisaCase $visaCase,
        private readonly User $actor,
    ) {
        $this->visaCase->loadMissing('client:id,full_name');
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
            'kind' => 'visa_case_assigned',
            'title' => $isSelfAssigned ? 'Visa case created' : 'Visa case assigned',
            'message' => $isSelfAssigned
                ? "You added {$this->visaCase->reference_code} to your case list."
                : "{$this->actor->name} assigned {$this->visaCase->reference_code} to you.",
            'context' => $this->visaCase->client?->full_name,
            'action_url' => route('visa-cases.show', $this->visaCase),
            'action_label' => 'Open case',
            'resource_id' => $this->visaCase->id,
            'resource_type' => 'visa_case',
            'resource_label' => $this->visaCase->reference_code,
        ];
    }
}
