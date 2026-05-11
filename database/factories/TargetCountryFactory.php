<?php

namespace Database\Factories;

use App\Models\TargetCountry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TargetCountry>
 */
class TargetCountryFactory extends Factory
{
    protected $model = TargetCountry::class;

    public function definition(): array
    {
        $name = fake()->unique()->country();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
