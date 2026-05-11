<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseCommunicationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ?string $mailSubject,
        private readonly ?string $mailBody,
        private readonly ?string $smsBody,
        private readonly ?string $smsFrom = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return array_values(array_filter([
            $this->mailBody ? 'mail' : null,
            $this->smsBody ? SmsChannel::class : null,
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->mailSubject ?: 'Agency update');

        foreach (preg_split("/\r\n|\n|\r/", $this->mailBody ?: '') as $line) {
            if (trim($line) === '') {
                continue;
            }

            $message->line($line);
        }

        return $message;
    }

    public function toSms(object $notifiable): ?SmsMessage
    {
        if (! $this->smsBody) {
            return null;
        }

        return new SmsMessage($this->smsBody, $this->smsFrom);
    }
}
