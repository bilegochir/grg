<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'source' => fake()->randomElement(LeadSource::cases()),
            'status' => fake()->randomElement(LeadStatus::cases()),
            'country_of_citizenship' => fake()->country(),
            'interested_visa_type' => fake()->randomElement(['Student', 'Tourist', 'Skilled Worker']),
            'education_history' => [
                [
                    'institution' => fake()->company().' University',
                    'degree' => 'Bachelor',
                    'field_of_study' => fake()->randomElement(['Business', 'Computer Science', 'Engineering']),
                    'start_date' => '2018-09-01',
                    'end_date' => '2022-06-01',
                    'notes' => fake()->sentence(),
                ],
            ],
            'work_experience' => [
                [
                    'company' => fake()->company(),
                    'title' => fake()->jobTitle(),
                    'location' => fake()->city(),
                    'start_date' => '2022-07-01',
                    'end_date' => null,
                    'is_current' => true,
                    'notes' => fake()->sentence(),
                ],
            ],
            'assigned_to_user_id' => User::factory(),
        ];
    }
}
