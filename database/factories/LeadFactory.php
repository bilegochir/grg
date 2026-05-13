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
            'pathway_interest' => fake()->randomElement(['Student', 'Visitor', 'Partner', 'Skilled']),
            'current_country' => fake()->country(),
            'relationship_status' => fake()->randomElement(['Single', 'Married']),
            'english_test_status' => fake()->randomElement(['Not booked yet', 'Planning IELTS', 'IELTS completed']),
            'highest_education' => fake()->randomElement(['Secondary school', 'Bachelor', 'Master']),
            'years_of_experience' => fake()->numberBetween(0, 8),
            'has_refusal_history' => fake()->boolean(20),
            'target_intake_date' => fake()->dateTimeBetween('+1 month', '+10 months'),
            'budget_range' => fake()->randomElement(['Under $5,000', '$5,000-$10,000', '$10,000+']),
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
