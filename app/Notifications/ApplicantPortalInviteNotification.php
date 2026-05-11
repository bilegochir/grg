<?php

namespace App\Notifications;

use App\Models\BusinessSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicantPortalInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $portalUrl,
        private readonly BusinessSetting $business,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your {$this->business->business_name} portal link")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('Your visa team prepared a secure portal so you can track case progress, upload documents, and check appointments and invoices.')
            ->action('Open your portal', $this->portalUrl)
            ->line('If the button does not work, copy this secure link into your browser:')
            ->line($this->portalUrl)
            ->line('For your safety, this access link expires in 14 days.');
    }
}
