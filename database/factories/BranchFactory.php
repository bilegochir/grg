<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $name = fake()->city().' Branch';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => Str::upper(Str::random(4)),
            'is_active' => true,
        ];
    }
}
