<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Applicant>
 */
class ApplicantFactory extends Factory
{
    protected $model = Applicant::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->date(),
            'nationality' => fake()->country(),
            'country_of_residence' => fake()->country(),
            'passport_number' => strtoupper(fake()->bothify('??######')),
            'passport_country' => fake()->country(),
            'passport_issued_at' => now()->subYears(2)->toDateString(),
            'passport_expires_at' => now()->addYears(8)->toDateString(),
            'travel_history' => [
                [
                    'country' => fake()->country(),
                    'purpose' => 'Tourism',
                    'year' => (int) now()->subYear()->format('Y'),
                ],
            ],
            'metadata' => [],
            'notification_preferences' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'locale' => 'en',
                'events' => [
                    'case_status_changes' => true,
                    'document_requests' => true,
                    'payment_reminders' => true,
                    'appointment_reminders' => true,
                    'messages' => true,
                ],
            ],
        ];
    }
}
