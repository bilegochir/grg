<?php

namespace App\Notifications\Messages;

class SmsMessage
{
    public function __construct(
        public readonly string $content,
        public readonly ?string $from = null,
    ) {
    }
}
