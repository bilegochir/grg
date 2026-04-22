<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Agency;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'owner_id' => null,
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-45 years', '-18 years'),
            'passport_number' => strtoupper(fake()->bothify('??######')),
            'passport_expiry_date' => fake()->dateTimeBetween('+6 months', '+8 years'),
            'marital_status' => fake()->randomElement(Client::MARITAL_STATUSES),
            'occupation' => fake()->jobTitle(),
            'current_address' => fake()->address(),
            'nationality' => fake()->country(),
            'destination_country' => fake()->randomElement(['Canada', 'Australia', 'United Kingdom', 'United States']),
            'lead_source' => fake()->randomElement(['Referral', 'Website', 'WhatsApp', 'Walk-in']),
            'status' => fake()->randomElement(ClientStatus::cases()),
            'notes' => fake()->sentence(),
            'family_members' => [
                [
                    'relationship' => 'Spouse',
                    'full_name' => fake()->name(),
                    'date_of_birth' => fake()->date(),
                    'nationality' => fake()->country(),
                    'occupation' => fake()->jobTitle(),
                    'is_accompanying' => fake()->boolean(),
                ],
            ],
            'education_history' => [
                [
                    'institution' => fake()->company() . ' University',
                    'qualification' => 'Bachelor',
                    'field_of_study' => 'Business',
                    'country' => fake()->country(),
                    'start_date' => fake()->date(),
                    'end_date' => fake()->date(),
                    'is_current' => false,
                ],
            ],
            'work_experiences' => [
                [
                    'employer' => fake()->company(),
                    'job_title' => fake()->jobTitle(),
                    'country' => fake()->country(),
                    'start_date' => fake()->date(),
                    'end_date' => fake()->date(),
                    'is_current' => false,
                    'summary' => fake()->sentence(),
                ],
            ],
        ];
    }
}
