<?php

namespace App\Notifications;

use App\Models\AgencyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AgencyInvitation $invitation,
        private readonly string $plainTextToken,
    ) {
        $this->invitation->loadMissing('agency', 'invitedBy');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to join {$this->invitation->agency?->name}")
            ->greeting('Team invitation')
            ->line("{$this->invitation->invitedBy?->name} invited you to join {$this->invitation->agency?->name} on Gereg.")
            ->line('Use the secure link below to create your password and access the company workspace.')
            ->action('Accept invitation', $this->acceptanceUrl())
            ->line("This invitation expires on {$this->invitation->expires_at?->format('M j, Y g:i A')}.")
            ->line('If you were not expecting this invite, you can ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'agency_invitation_id' => $this->invitation->id,
        ];
    }

    public function acceptanceUrl(): string
    {
        return route('team-invitations.accept', [
            'agencyInvitation' => $this->invitation,
            'token' => $this->plainTextToken,
        ]);
    }
}
