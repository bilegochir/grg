<?php

namespace App\Notifications;

use App\Models\BusinessSetting;
use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TeamInvitation $invitation,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $business = BusinessSetting::current();
        $acceptUrl = route('team-invitations.accept', $this->invitation->token);

        return (new MailMessage())
            ->subject("You're invited to join {$business->business_name}")
            ->greeting('Hello,')
            ->line("You've been invited to join {$business->business_name} as {$this->invitation->role->name}.")
            ->line($this->invitation->branch ? "Branch: {$this->invitation->branch->name}" : 'You can join the shared workspace.')
            ->action('Accept invitation', $acceptUrl)
            ->line('This invitation expires in 7 days.')
            ->line('If you were not expecting this invite, you can safely ignore this email.');
    }
}
