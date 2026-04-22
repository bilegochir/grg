<?php

namespace Database\Factories;

use App\Enums\VisaCaseStatus;
use App\Models\Agency;
use App\Models\Client;
use App\Models\VisaCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCase>
 */
class VisaCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $visaType = fake()->randomElement(['Tourist visa', 'Student visa', 'Work permit']);

        return [
            'agency_id' => Agency::factory(),
            'client_id' => Client::factory(),
            'assigned_user_id' => null,
            'reference_code' => null,
            'visa_type' => $visaType,
            'destination_country' => fake()->randomElement(['Canada', 'Australia', 'United Kingdom', 'United States']),
            'institution_name' => $visaType === 'Student visa' ? fake()->company().' University' : null,
            'status' => fake()->randomElement(VisaCaseStatus::cases()),
            'submitted_at' => null,
            'decision_at' => null,
            'notes' => fake()->sentence(),
        ];
    }
}
