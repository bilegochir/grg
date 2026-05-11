<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCaseMessage>
 */
class VisaCaseMessageFactory extends Factory
{
    protected $model = VisaCaseMessage::class;

    public function definition(): array
    {
        return [
            'visa_case_id' => VisaCase::factory(),
            'sent_by_user_id' => User::factory(),
            'sender_type' => 'staff',
            'direction' => 'outbound',
            'channel' => fake()->randomElement(['email', 'sms']),
            'notification_event' => fake()->randomElement([
                'case_status_changes',
                'document_requests',
                'messages',
            ]),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'metadata' => [],
            'sent_at' => now(),
        ];
    }
}
