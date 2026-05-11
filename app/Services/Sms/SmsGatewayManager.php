<?php

namespace App\Services\Sms;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsGatewayManager
{
    public function send(string $to, string $content, ?string $from = null): void
    {
        $settings = BusinessSetting::current();
        $provider = $settings->sms_provider ?: 'log';
        $from ??= $settings->sms_sender ?: config('services.twilio.from') ?: config('services.local_sms.from');

        match ($provider) {
            'twilio' => $this->sendViaTwilio($to, $content, $from),
            'local_gateway' => $this->sendViaLocalGateway($to, $content, $from),
            default => $this->sendViaLog($to, $content, $from),
        };
    }

    private function sendViaTwilio(string $to, string $content, ?string $from): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $defaultFrom = $from ?: config('services.twilio.from');

        if (! $sid || ! $token || ! $defaultFrom) {
            $this->sendViaLog($to, $content, $from);
            return;
        }

        Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $to,
                'From' => $defaultFrom,
                'Body' => $content,
            ])
            ->throw();
    }

    private function sendViaLocalGateway(string $to, string $content, ?string $from): void
    {
        $url = config('services.local_sms.url');
        $token = config('services.local_sms.token');

        if (! $url) {
            $this->sendViaLog($to, $content, $from);
            return;
        }

        Http::withToken($token)
            ->post($url, [
                'to' => $to,
                'from' => $from ?: config('services.local_sms.from'),
                'message' => $content,
            ])
            ->throw();
    }

    private function sendViaLog(string $to, string $content, ?string $from): void
    {
        Log::info('SMS notification prepared.', [
            'to' => $to,
            'from' => $from,
            'content' => $content,
        ]);
    }
}
