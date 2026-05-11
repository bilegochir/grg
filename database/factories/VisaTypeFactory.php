<?php

namespace Database\Factories;

use App\Models\TargetCountry;
use App\Models\VisaType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VisaType>
 */
class VisaTypeFactory extends Factory
{
    protected $model = VisaType::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Tourist', 'Student', 'Work', 'PR', 'Business']);

        return [
            'target_country_id' => TargetCountry::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
