<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCaseNote>
 */
class VisaCaseNoteFactory extends Factory
{
    protected $model = VisaCaseNote::class;

    public function definition(): array
    {
        return [
            'visa_case_id' => VisaCase::factory(),
            'created_by_user_id' => User::factory(),
            'body' => fake()->sentence(12),
            'is_client_visible' => false,
        ];
    }
}
