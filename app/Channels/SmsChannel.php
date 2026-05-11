<?php

namespace App\Channels;

use App\Notifications\Messages\SmsMessage;
use App\Services\Sms\SmsGatewayManager;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(
        private readonly SmsGatewayManager $smsGatewayManager,
    ) {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        /** @var SmsMessage|null $message */
        $message = $notification->toSms($notifiable);

        if ($message === null) {
            return;
        }

        $phoneNumber = $notifiable->routeNotificationFor('sms', $notification);

        if (! $phoneNumber) {
            return;
        }

        $this->smsGatewayManager->send(
            to: $phoneNumber,
            content: $message->content,
            from: $message->from,
        );
    }
}
